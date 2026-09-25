<?php
namespace App\Policies;
use App\Domain\Identity\Authorization;
use App\Domain\Shared\Role;
use App\Models\Asset;
use App\Models\User;
class AssetPolicy
{
    public function view(User $user, Asset $asset): bool { return Authorization::membership($user,$asset->organization_id)!==null; }
    public function update(User $user, Asset $asset): bool { return Authorization::hasAnyRole($user,$asset->organization_id,[Role::Administrator,Role::Supervisor]); }
    public function assign(User $user, Asset $asset): bool { return $this->update($user,$asset); }
}
