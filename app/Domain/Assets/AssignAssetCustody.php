<?php

namespace App\Domain\Assets;

use App\Domain\Audit\AuditRecorder;
use App\Domain\Identity\Authorization;
use App\Domain\Shared\Role;
use App\Domain\Sync\SyncRejected;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\OperationalEvent;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;

final class AssignAssetCustody
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function execute(User $actor, Organization $organization, array $payload, string $clientOperationId): array
    {
        if (! Authorization::hasAnyRole($actor, $organization->id, [Role::Administrator, Role::Supervisor])) {
            throw new SyncRejected('AUTHORITY_REVOKED');
        }

        $asset = Asset::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $payload['asset_public_id'] ?? '')
            ->lockForUpdate()
            ->first();
        if (! $asset) throw new SyncRejected('RESOURCE_NOT_FOUND');

        $baseRevision = (int) ($payload['base_revision'] ?? 0);
        if ($baseRevision !== $asset->revision) {
            throw new SyncRejected('STALE_CONFLICT', $this->assetSnapshot($asset));
        }

        $assignee = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', (int) ($payload['assignee_user_id'] ?? 0))
            ->where('is_active', true)
            ->first();
        if (! $assignee) throw new SyncRejected('INVALID_ASSIGNEE');

        $active = AssetAssignment::query()->where('asset_id', $asset->id)->whereNull('ended_at')->lockForUpdate()->first();
        if ($active && $active->assignee_user_id === $assignee->user_id) {
            throw new SyncRejected('ALREADY_ASSIGNED', $this->assetSnapshot($asset));
        }
        if ($active) {
            $active->forceFill(['ended_at' => now(), 'ended_by_user_id' => $actor->id])->save();
        }

        $assignment = AssetAssignment::query()->create([
            'organization_id' => $organization->id,
            'asset_id' => $asset->id,
            'assignee_user_id' => $assignee->user_id,
            'assigned_by_user_id' => $actor->id,
            'reason' => $payload['reason'] ?? null,
            'started_at' => now(),
        ]);

        $asset->forceFill(['status' => $asset->status === 'available' ? 'deployed' : $asset->status, 'revision' => $asset->revision + 1])->save();

        $this->audit->record($organization, $actor, 'asset.custody.assigned', 'asset', $asset->public_id, [
            'assignment_id' => $assignment->id,
            'assignee_user_id' => $assignee->user_id,
            'previous_assignee_user_id' => $active?->assignee_user_id,
            'asset_revision' => $asset->revision,
        ], $clientOperationId);

        OperationalEvent::query()->create([
            'organization_id' => $organization->id,
            'event_type' => 'asset.custody.assigned',
            'severity' => 'info',
            'message' => "{$asset->code} assigned to user #{$assignee->user_id}",
            'context' => ['asset_public_id' => $asset->public_id, 'assignment_id' => $assignment->id],
            'created_at' => now(),
        ]);

        return ['asset' => $this->assetSnapshot($asset), 'assignment_id' => $assignment->id];
    }

    private function assetSnapshot(Asset $asset): array
    {
        $active = AssetAssignment::query()->where('asset_id', $asset->id)->whereNull('ended_at')->first();
        return [
            'public_id' => $asset->public_id,
            'code' => $asset->code,
            'status' => $asset->status,
            'revision' => $asset->revision,
            'assignee_user_id' => $active?->assignee_user_id,
        ];
    }
}
