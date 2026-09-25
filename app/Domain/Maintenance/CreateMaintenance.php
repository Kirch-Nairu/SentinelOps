<?php
namespace App\Domain\Maintenance;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Identity\Authorization;
use App\Domain\Shared\Role;
use App\Models\Asset;
use App\Models\Incident;
use App\Models\MaintenanceRecord;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
final class CreateMaintenance
{
    public function __construct(private readonly AuditRecorder $audit) {}
    public function execute(User $actor, Organization $organization, Asset $asset, ?Incident $incident, string $description): MaintenanceRecord
    {
        if ($asset->organization_id !== $organization->id || ($incident && $incident->organization_id !== $organization->id)) throw new AuthorizationException();
        if (! Authorization::hasAnyRole($actor,$organization->id,[Role::Administrator,Role::Supervisor,Role::SecurityOfficer])) throw new AuthorizationException();
        $asset = Asset::query()->whereKey($asset->id)->lockForUpdate()->firstOrFail();
        $record = MaintenanceRecord::query()->create(['public_id'=>(string)Str::uuid(),'organization_id'=>$organization->id,'asset_id'=>$asset->id,'incident_id'=>$incident?->id,'opened_by_user_id'=>$actor->id,'description'=>$description,'status'=>'open']);
        $asset->forceFill(['status'=>'maintenance','revision'=>$asset->revision+1])->save();
        $this->audit->record($organization,$actor,'maintenance.opened','maintenance',$record->public_id,['asset_public_id'=>$asset->public_id,'incident_public_id'=>$incident?->public_id]);
        return $record;
    }
}
