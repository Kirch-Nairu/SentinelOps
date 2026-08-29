<?php

namespace App\Domain\Identity;

use App\Domain\Shared\Role;
use App\Models\OrganizationMembership;
use App\Models\User;

final class Authorization
{
    public static function membership(User $user, int $organizationId): ?OrganizationMembership
    {
        return OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }

    /** @param list<Role> $roles */
    public static function hasAnyRole(User $user, int $organizationId, array $roles): bool
    {
        $membership = self::membership($user, $organizationId);
        if (! $membership) return false;
        foreach ($roles as $role) {
            if ($membership->role === $role) return true;
        }
        return false;
    }
}
