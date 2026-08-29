<?php

namespace Tests\Feature;

use App\Domain\Shared\Role;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AuditEvent;
use App\Models\Evidence;
use App\Models\EvidenceStaging;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\SyncOperation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SentinelOpsInvariantTest extends TestCase
{
    use RefreshDatabase;

    private function org(string $suffix = ''): Organization
    {
        return Organization::create(['public_id'=>(string)Str::uuid(),'name'=>'Ops '.$suffix,'slug'=>'ops-'.Str::lower(Str::random(8))]);
    }

    private function member(Organization $org, Role $role, string $suffix = ''): User
    {
        $user = User::create(['name'=>$role->name.' '.$suffix,'email'=>Str::lower($role->name).Str::random(6).'@example.test','password'=>'secret-pass']);
        OrganizationMembership::create(['organization_id'=>$org->id,'user_id'=>$user->id,'role'=>$role->value,'is_active'=>true]);
        return $user;
    }

    private function asset(Organization $org, int $revision = 1): Asset
    {
        return Asset::create(['public_id'=>(string)Str::uuid(),'organization_id'=>$org->id,'code'=>'ASSET-'.Str::upper(Str::random(7)),'name'=>'Field Asset','status'=>'available','revision'=>$revision]);
    }

    private function sync(User $user, Organization $org, array $operation)
    {
        return $this->actingAs($user)->withSession(['organization_id'=>$org->id])->postJson('/api/sync/operations',['operations'=>[$operation]]);
    }

    public function test_authentication_establishes_an_active_server_workspace(): void
    {
        $org=$this->org('Auth');
        $user=$this->member($org,Role::Technician);
        $this->post('/login',['email'=>$user->email,'password'=>'wrong'])->assertSessionHasErrors('email');
        $this->post('/login',['email'=>$user->email,'password'=>'secret-pass'])->assertRedirect('/dashboard')->assertSessionHas('organization_id',$org->id);
    }

    public function test_cross_organization_asset_reads_and_mutations_are_rejected(): void
    {
        $orgA=$this->org('A'); $orgB=$this->org('B');
        $supervisor=$this->member($orgA,Role::Supervisor); $foreign=$this->asset($orgB);
        $this->actingAs($supervisor)->withSession(['organization_id'=>$orgA->id])->get('/assets/'.$foreign->public_id)->assertNotFound();
        $op=['client_operation_id'=>(string)Str::uuid(),'client_sequence'=>1,'type'=>'asset.assign','payload'=>['asset_public_id'=>$foreign->public_id,'base_revision'=>1,'assignee_user_id'=>$supervisor->id]];
        $this->sync($supervisor,$orgA,$op)->assertOk()->assertJsonPath('results.0.status','rejected')->assertJsonPath('results.0.code','RESOURCE_NOT_FOUND');
        $this->actingAs($supervisor)->withSession(['organization_id'=>$orgA->id])->postJson('/api/sync/operations',['organization_id'=>$orgB->id,'operations'=>[$op]])->assertStatus(422)->assertJsonPath('code','UNTRUSTED_TENANT_IDENTIFIER');
    }

    public function test_auditor_is_read_only_for_asset_creation(): void
    {
        $org=$this->org(); $auditor=$this->member($org,Role::Auditor);
        $this->actingAs($auditor)->withSession(['organization_id'=>$org->id])->post('/assets',['code'=>'NEW-1','name'=>'Nope'])->assertForbidden();
        $this->assertDatabaseMissing('assets',['organization_id'=>$org->id,'code'=>'NEW-1']);
    }

    public function test_five_replays_of_the_same_assignment_produce_one_authoritative_effect(): void
    {
        $org=$this->org(); $supervisor=$this->member($org,Role::Supervisor); $technician=$this->member($org,Role::Technician); $asset=$this->asset($org);
        $id=(string)Str::uuid();
        $op=['client_operation_id'=>$id,'client_sequence'=>7,'type'=>'asset.assign','payload'=>['asset_public_id'=>$asset->public_id,'base_revision'=>1,'assignee_user_id'=>$technician->id,'reason'=>'deploy']];
        for($i=0;$i<5;$i++) $this->sync($supervisor,$org,$op)->assertOk()->assertJsonPath('results.0.status','accepted');
        $this->assertSame(1,AssetAssignment::where('asset_id',$asset->id)->whereNull('ended_at')->count());
        $this->assertSame(1,SyncOperation::where('organization_id',$org->id)->where('client_operation_id',$id)->count());
        $this->assertSame(2,$asset->fresh()->revision);
        $this->assertSame(1,AuditEvent::where('event_type','asset.custody.assigned')->count());
    }

    public function test_two_offline_assignments_are_resolved_by_server_revision_not_last_write_wins(): void
    {
        $org=$this->org(); $s1=$this->member($org,Role::Supervisor,'1'); $s2=$this->member($org,Role::Supervisor,'2'); $t1=$this->member($org,Role::Technician,'1'); $t2=$this->member($org,Role::Technician,'2'); $asset=$this->asset($org);
        $op1=['client_operation_id'=>(string)Str::uuid(),'client_sequence'=>1,'type'=>'asset.assign','payload'=>['asset_public_id'=>$asset->public_id,'base_revision'=>1,'assignee_user_id'=>$t1->id]];
        $op2=['client_operation_id'=>(string)Str::uuid(),'client_sequence'=>1,'type'=>'asset.assign','payload'=>['asset_public_id'=>$asset->public_id,'base_revision'=>1,'assignee_user_id'=>$t2->id]];
        $this->sync($s1,$org,$op1)->assertJsonPath('results.0.status','accepted');
        $this->sync($s2,$org,$op2)->assertJsonPath('results.0.status','rejected')->assertJsonPath('results.0.code','STALE_CONFLICT')->assertJsonPath('results.0.reconciliation.assignee_user_id',$t1->id);
        $active=AssetAssignment::where('asset_id',$asset->id)->whereNull('ended_at')->firstOrFail();
        $this->assertSame($t1->id,$active->assignee_user_id);
        $this->assertSame(2,$asset->fresh()->revision);
    }

    public function test_current_authorization_is_rechecked_when_an_offline_command_syncs(): void
    {
        $org=$this->org(); $actor=$this->member($org,Role::Supervisor); $target=$this->member($org,Role::Technician); $asset=$this->asset($org);
        $queued=['client_operation_id'=>(string)Str::uuid(),'client_sequence'=>1,'type'=>'asset.assign','payload'=>['asset_public_id'=>$asset->public_id,'base_revision'=>1,'assignee_user_id'=>$target->id]];
        OrganizationMembership::where('organization_id',$org->id)->where('user_id',$actor->id)->update(['role'=>Role::Auditor->value]);
        $this->sync($actor,$org,$queued)->assertJsonPath('results.0.status','rejected')->assertJsonPath('results.0.code','AUTHORITY_REVOKED');
        $this->assertDatabaseMissing('asset_assignments',['asset_id'=>$asset->id]);
    }

    public function test_stale_incident_is_rejected_and_staged_evidence_remains_unattached(): void
    {
        Storage::fake('local');
        $org=$this->org(); $tech=$this->member($org,Role::Technician); $asset=$this->asset($org);
        $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        $stage=$this->actingAs($tech)->withSession(['organization_id'=>$org->id])->postJson('/api/evidence/stage',['evidence'=>UploadedFile::fake()->createWithContent('damage.png',$png)])->assertCreated();
        $token=$stage->json('token');
        $op=['client_operation_id'=>(string)Str::uuid(),'client_sequence'=>1,'type'=>'incident.create','payload'=>['asset_public_id'=>$asset->public_id,'base_revision'=>99,'severity'=>'high','finding'=>'Fuel line damaged','created_offline'=>true,'evidence_tokens'=>[$token]]];
        $this->sync($tech,$org,$op)->assertJsonPath('results.0.code','STALE_CONFLICT');
        $this->assertSame(0,Evidence::count());
        $staging=EvidenceStaging::where('token',$token)->firstOrFail();
        $this->assertNull($staging->attached_at);
        Storage::disk('local')->assertExists($staging->storage_key);
    }

    public function test_incident_evidence_and_asset_status_commit_together_then_incident_closes_without_erasing_history(): void
    {
        Storage::fake('local');
        $org=$this->org(); $tech=$this->member($org,Role::Technician); $security=$this->member($org,Role::SecurityOfficer); $asset=$this->asset($org);
        $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        $stage=$this->actingAs($tech)->withSession(['organization_id'=>$org->id])->postJson('/api/evidence/stage',['evidence'=>UploadedFile::fake()->createWithContent('damage.png',$png)])->assertCreated();
        $op=['client_operation_id'=>(string)Str::uuid(),'client_sequence'=>1,'type'=>'incident.create','payload'=>['asset_public_id'=>$asset->public_id,'base_revision'=>1,'severity'=>'high','finding'=>'Fuel line appears damaged.','created_offline'=>true,'evidence_tokens'=>[$stage->json('token')]]];
        $response=$this->sync($tech,$org,$op)->assertJsonPath('results.0.status','accepted');
        $incidentId=$response->json('results.0.reconciliation.incident.public_id');
        $this->assertSame('damaged',$asset->fresh()->status); $this->assertSame(2,$asset->fresh()->revision); $this->assertSame(1,Evidence::count());
        $this->actingAs($tech)->withSession(['organization_id'=>$org->id])->postJson('/api/incidents/'.$incidentId.'/close')->assertForbidden();
        $this->actingAs($security)->withSession(['organization_id'=>$org->id])->postJson('/api/incidents/'.$incidentId.'/close')->assertOk()->assertJsonPath('incident.status','closed');
        $this->assertSame(1,Evidence::count());
        $this->assertGreaterThanOrEqual(2,AuditEvent::where('organization_id',$org->id)->count());
        $this->assertSame('closed',Incident::where('public_id',$incidentId)->value('status'));
    }

    public function test_idempotency_identity_cannot_be_reused_for_a_different_payload(): void
    {
        $org=$this->org();$supervisor=$this->member($org,Role::Supervisor);$t1=$this->member($org,Role::Technician,'1');$t2=$this->member($org,Role::Technician,'2');$asset=$this->asset($org);$id=(string)Str::uuid();
        $base=['client_operation_id'=>$id,'client_sequence'=>1,'type'=>'asset.assign'];
        $this->sync($supervisor,$org,$base+['payload'=>['asset_public_id'=>$asset->public_id,'base_revision'=>1,'assignee_user_id'=>$t1->id]])->assertJsonPath('results.0.status','accepted');
        $this->sync($supervisor,$org,$base+['payload'=>['asset_public_id'=>$asset->public_id,'base_revision'=>1,'assignee_user_id'=>$t2->id]])->assertJsonPath('results.0.code','IDEMPOTENCY_KEY_REUSE');
        $this->assertSame($t1->id,AssetAssignment::where('asset_id',$asset->id)->whereNull('ended_at')->value('assignee_user_id'));
        $this->assertDatabaseHas('audit_events',['event_type'=>'sync.idempotency_identity_reused']);
    }

    public function test_privileged_role_change_requires_recent_reauthentication(): void
    {
        $org=$this->org();$admin=$this->member($org,Role::Administrator,'1');$this->member($org,Role::Administrator,'2');$target=$this->member($org,Role::Supervisor);$membership=OrganizationMembership::where('organization_id',$org->id)->where('user_id',$target->id)->firstOrFail();
        $this->actingAs($admin)->withSession(['organization_id'=>$org->id])->patchJson('/api/admin/memberships/'.$membership->id,['role'=>Role::Auditor->value])->assertForbidden()->assertJsonPath('code','STEP_UP_REQUIRED');
        $this->postJson('/api/reauthenticate',['password'=>'secret-pass'])->assertOk();
        $this->patchJson('/api/admin/memberships/'.$membership->id,['role'=>Role::Auditor->value])->assertOk()->assertJsonPath('membership.role',Role::Auditor->value);
    }

    public function test_recovery_verifier_refuses_missing_evidence_bytes(): void
    {
        Storage::fake('local');
        $org=$this->org();$tech=$this->member($org,Role::Technician);$asset=$this->asset($org);
        $key='sentinelops/evidence/objects/proof'; Storage::disk('local')->put($key,'proof-bytes');
        $incident=Incident::create(['public_id'=>(string)Str::uuid(),'organization_id'=>$org->id,'asset_id'=>$asset->id,'created_by_user_id'=>$tech->id,'severity'=>'low','finding'=>'proof','status'=>'open','created_offline'=>false,'asset_revision_at_creation'=>1,'revision'=>1]);
        Evidence::create(['public_id'=>(string)Str::uuid(),'organization_id'=>$org->id,'incident_id'=>$incident->id,'uploaded_by_user_id'=>$tech->id,'storage_key'=>$key,'original_name'=>'proof.bin','mime_type'=>'image/png','size_bytes'=>11,'sha256'=>hash('sha256','proof-bytes'),'created_at'=>now()]);
        $this->artisan('sentinelops:verify-recovery')->assertSuccessful()->expectsOutputToContain('RECOVERY VERIFIED');
        Storage::disk('local')->delete($key);
        $this->artisan('sentinelops:verify-recovery')->assertFailed()->expectsOutputToContain('RECOVERY INCOMPLETE');
    }

    public function test_audit_rows_are_database_append_only(): void
    {
        $org=$this->org();$actor=$this->member($org,Role::Administrator);
        $event=AuditEvent::create(['event_id'=>(string)Str::uuid(),'organization_id'=>$org->id,'actor_user_id'=>$actor->id,'event_type'=>'test','subject_type'=>'asset','subject_id'=>'1','data'=>[],'created_at'=>now()]);
        $this->expectException(QueryException::class);
        \DB::table('audit_events')->where('id',$event->id)->update(['event_type'=>'tampered']);
    }

    public function test_attached_evidence_metadata_is_database_immutable(): void
    {
        Storage::fake('local');
        $org=$this->org();$tech=$this->member($org,Role::Technician);$asset=$this->asset($org);$incident=Incident::create(['public_id'=>(string)Str::uuid(),'organization_id'=>$org->id,'asset_id'=>$asset->id,'created_by_user_id'=>$tech->id,'severity'=>'low','finding'=>'proof','status'=>'open','created_offline'=>false,'asset_revision_at_creation'=>1,'revision'=>1]);
        $record=Evidence::create(['public_id'=>(string)Str::uuid(),'organization_id'=>$org->id,'incident_id'=>$incident->id,'uploaded_by_user_id'=>$tech->id,'storage_key'=>'proof','original_name'=>'proof.png','mime_type'=>'image/png','size_bytes'=>1,'sha256'=>str_repeat('a',64),'created_at'=>now()]);
        $this->expectException(QueryException::class);
        \DB::table('evidence')->where('id',$record->id)->update(['original_name'=>'changed.png']);
    }
}
