<?php

namespace App\Domain\Incidents;

use App\Domain\Audit\AuditRecorder;
use App\Domain\Identity\Authorization;
use App\Domain\Shared\Role;
use App\Domain\Sync\SyncRejected;
use App\Models\Asset;
use App\Models\Evidence;
use App\Models\EvidenceStaging;
use App\Models\Incident;
use App\Models\OperationalEvent;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CreateIncident
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function execute(User $actor, Organization $organization, array $payload, string $clientOperationId): array
    {
        if (! Authorization::hasAnyRole($actor, $organization->id, [Role::Administrator, Role::Supervisor, Role::Technician, Role::SecurityOfficer])) {
            throw new SyncRejected('AUTHORITY_REVOKED');
        }

        $asset = Asset::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $payload['asset_public_id'] ?? '')
            ->lockForUpdate()
            ->first();
        if (! $asset) throw new SyncRejected('RESOURCE_NOT_FOUND');
        if ($asset->status === 'retired') throw new SyncRejected('ASSET_RETIRED', ['asset' => $this->assetSnapshot($asset)]);

        $baseRevision = (int) ($payload['base_revision'] ?? 0);
        if ($baseRevision !== $asset->revision) {
            throw new SyncRejected('STALE_CONFLICT', ['asset' => $this->assetSnapshot($asset)]);
        }

        $severity = (string) ($payload['severity'] ?? '');
        if (! in_array($severity, ['low','medium','high','critical'], true)) throw new SyncRejected('INVALID_PAYLOAD');
        $finding = trim((string) ($payload['finding'] ?? ''));
        if ($finding === '' || mb_strlen($finding) > 5000) throw new SyncRejected('INVALID_PAYLOAD');

        $staged = [];
        foreach (array_values(array_unique($payload['evidence_tokens'] ?? [])) as $token) {
            $row = EvidenceStaging::query()
                ->where('token', $token)
                ->where('organization_id', $organization->id)
                ->where('uploaded_by_user_id', $actor->id)
                ->lockForUpdate()
                ->first();
            if (! $row || $row->attached_at || $row->expires_at->isPast()) throw new SyncRejected('INVALID_EVIDENCE_TOKEN');
            if (! Storage::disk('local')->exists($row->storage_key)) throw new SyncRejected('EVIDENCE_BYTES_MISSING');
            $actualHash = hash_file('sha256', Storage::disk('local')->path($row->storage_key));
            if (! hash_equals($row->sha256, $actualHash)) throw new SyncRejected('EVIDENCE_INTEGRITY_FAILURE');
            $staged[] = $row;
        }

        $incident = Incident::query()->create([
            'public_id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'asset_id' => $asset->id,
            'created_by_user_id' => $actor->id,
            'severity' => $severity,
            'finding' => $finding,
            'status' => 'open',
            'created_offline' => (bool) ($payload['created_offline'] ?? false),
            'asset_revision_at_creation' => $asset->revision,
            'revision' => 1,
        ]);

        $evidencePublicIds = [];
        foreach ($staged as $row) {
            $publicId = (string) Str::uuid();
            Evidence::query()->create([
                'public_id' => $publicId,
                'organization_id' => $organization->id,
                'incident_id' => $incident->id,
                'uploaded_by_user_id' => $actor->id,
                'storage_key' => $row->storage_key,
                'original_name' => $row->original_name,
                'mime_type' => $row->mime_type,
                'size_bytes' => $row->size_bytes,
                'sha256' => $row->sha256,
                'created_at' => now(),
            ]);
            $row->forceFill(['attached_at' => now()])->save();
            $evidencePublicIds[] = $publicId;
        }

        if (in_array($severity, ['high','critical'], true) && $asset->status !== 'maintenance') {
            $asset->status = 'damaged';
        }
        $asset->revision++;
        $asset->save();

        $this->audit->record($organization, $actor, 'incident.created', 'incident', $incident->public_id, [
            'asset_public_id' => $asset->public_id,
            'severity' => $severity,
            'created_offline' => $incident->created_offline,
            'evidence_count' => count($staged),
            'asset_revision' => $asset->revision,
        ], $clientOperationId);

        OperationalEvent::query()->create([
            'organization_id' => $organization->id,
            'event_type' => 'incident.created',
            'severity' => in_array($severity, ['high','critical'], true) ? 'high' : 'info',
            'message' => strtoupper($severity)." incident on {$asset->code}",
            'context' => ['incident_public_id' => $incident->public_id, 'asset_public_id' => $asset->public_id],
            'created_at' => now(),
        ]);

        return [
            'incident' => ['public_id' => $incident->public_id, 'status' => $incident->status, 'severity' => $incident->severity, 'finding' => $incident->finding],
            'asset' => $this->assetSnapshot($asset),
            'evidence_public_ids' => $evidencePublicIds,
        ];
    }

    private function assetSnapshot(Asset $asset): array
    {
        return ['public_id'=>$asset->public_id,'code'=>$asset->code,'status'=>$asset->status,'revision'=>$asset->revision];
    }
}
