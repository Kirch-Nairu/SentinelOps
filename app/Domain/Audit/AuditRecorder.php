<?php

namespace App\Domain\Audit;

use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;

final class AuditRecorder
{
    public function record(
        Organization $organization,
        ?User $actor,
        string $eventType,
        string $subjectType,
        string|int $subjectId,
        array $data = [],
        ?string $clientOperationId = null,
    ): AuditEvent {
        return AuditEvent::query()->create([
            'event_id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'actor_user_id' => $actor?->id,
            'client_operation_id' => $clientOperationId,
            'event_type' => $eventType,
            'subject_type' => $subjectType,
            'subject_id' => (string) $subjectId,
            'data' => $data,
            'created_at' => now(),
        ]);
    }
}
