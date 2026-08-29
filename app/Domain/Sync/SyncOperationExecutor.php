<?php

namespace App\Domain\Sync;

use App\Domain\Assets\AssignAssetCustody;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Incidents\CreateIncident;
use App\Models\Organization;
use App\Models\SyncOperation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SyncOperationExecutor
{
    public function __construct(
        private readonly AssignAssetCustody $assignAsset,
        private readonly CreateIncident $createIncident,
        private readonly AuditRecorder $audit,
    ) {}

    public function execute(User $user, Organization $organization, array $input): array
    {
        $clientId = (string) $input['client_operation_id'];
        $type = (string) $input['type'];
        $payload = (array) ($input['payload'] ?? []);
        $hash = CanonicalPayload::hash($type, $payload);

        return DB::transaction(function () use ($user, $organization, $input, $clientId, $type, $payload, $hash) {
            // PostgreSQL advisory transaction lock serializes the idempotency identity before lookup/insert.
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', [$organization->id.':'.$clientId]);

            $existing = SyncOperation::query()
                ->where('organization_id', $organization->id)
                ->where('client_operation_id', $clientId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->user_id !== $user->id || ! hash_equals($existing->payload_hash, $hash)) {
                    $this->audit->record($organization, $user, 'sync.idempotency_identity_reused', 'sync_operation', $clientId, ['operation_type'=>$type], $clientId);
                    return ['client_operation_id'=>$clientId,'status'=>'rejected','code'=>'IDEMPOTENCY_KEY_REUSE','reconciliation'=>null];
                }
                return $existing->result ?? ['client_operation_id'=>$clientId,'status'=>$existing->status,'code'=>$existing->rejection_code];
            }

            $operation = SyncOperation::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'client_operation_id' => $clientId,
                'client_sequence' => (int) ($input['client_sequence'] ?? 0),
                'operation_type' => $type,
                'payload_hash' => $hash,
                'status' => 'processing',
            ]);

            try {
                $domain = match ($type) {
                    'asset.assign' => $this->assignAsset->execute($user, $organization, $payload, $clientId),
                    'incident.create' => $this->createIncident->execute($user, $organization, $payload, $clientId),
                    default => throw new SyncRejected('UNSUPPORTED_OPERATION'),
                };

                $result = ['client_operation_id'=>$clientId,'status'=>'accepted','code'=>null,'reconciliation'=>$domain];
                $operation->forceFill(['status'=>'accepted','result'=>$result,'executed_at'=>now()])->save();
                return $result;
            } catch (SyncRejected $rejected) {
                $result = ['client_operation_id'=>$clientId,'status'=>'rejected','code'=>$rejected->reasonCode,'reconciliation'=>$rejected->reconciliation];
                $operation->forceFill(['status'=>'rejected','rejection_code'=>$rejected->reasonCode,'result'=>$result,'executed_at'=>now()])->save();
                $this->audit->record($organization,$user,'sync.rejected','sync_operation',$clientId,['operation_type'=>$type,'reason'=>$rejected->reasonCode],$clientId);
                return $result;
            }
        }, 3);
    }
}
