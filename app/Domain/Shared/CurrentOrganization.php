<?php

namespace App\Domain\Shared;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class CurrentOrganization
{
    public function resolve(User $user): Organization
    {
        $organizationId = session('organization_id');
        $membership = OrganizationMembership::query()
            ->with('organization')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->first();

        if (! $membership) {
            throw new AuthorizationException('No active organization membership.');
        }

        if (! $organizationId) session(['organization_id' => $membership->organization_id]);
        return $membership->organization;
    }
}
