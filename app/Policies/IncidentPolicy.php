<?php
namespace App\Policies;
use App\Domain\Identity\Authorization;
use App\Domain\Shared\Role;
use App\Models\Incident;
use App\Models\User;
class IncidentPolicy
{
    public function view(User $user, Incident $incident): bool { return Authorization::membership($user,$incident->organization_id)!==null; }
    public function close(User $user, Incident $incident): bool { return Authorization::hasAnyRole($user,$incident->organization_id,[Role::Administrator,Role::Supervisor,Role::SecurityOfficer]); }
}
