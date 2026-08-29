<?php
namespace App\Policies;
use App\Domain\Identity\Authorization;
use App\Domain\Shared\Role;
use App\Models\Evidence;
use App\Models\User;
class EvidencePolicy
{
    public function view(User $user, Evidence $evidence): bool
    {
        if (Authorization::hasAnyRole($user,$evidence->organization_id,[Role::Administrator,Role::Supervisor,Role::SecurityOfficer,Role::Auditor])) return true;
        if (! Authorization::hasAnyRole($user,$evidence->organization_id,[Role::Technician])) return false;
        $incident=$evidence->relationLoaded('incident')?$evidence->incident:$evidence->incident()->with('asset.activeAssignment')->first();
        return $incident?->created_by_user_id===$user->id || $incident?->asset?->activeAssignment?->assignee_user_id===$user->id;
    }
}
