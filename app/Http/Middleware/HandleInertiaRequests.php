<?php
namespace App\Http\Middleware;
use App\Domain\Shared\CurrentOrganization;
use Illuminate\Http\Request;
use Inertia\Middleware;
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';
    public function share(Request $request): array
    {
        $user = $request->user();
        $organization = null;
        $membership = null;
        if ($user) {
            try {
                $organization = app(CurrentOrganization::class)->resolve($user);
                $membership = $user->memberships()->where('organization_id',$organization->id)->where('is_active',true)->first();
            } catch (\Throwable) {}
        }
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email] : null,
                'organization' => $organization ? ['public_id'=>$organization->public_id,'name'=>$organization->name,'slug'=>$organization->slug] : null,
                'role' => $membership?->role?->value,
            ],
            'flash' => ['error' => fn () => $request->session()->get('error')],
        ]);
    }
}
