<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OrganizationMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        if (! Auth::attempt($credentials, false)) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are invalid.']);
        }

        $request->session()->regenerate();
        $membership = OrganizationMembership::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
        if (! $membership) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['email' => 'This account has no active SentinelOps workspace.']);
        }
        $request->session()->put('organization_id', $membership->organization_id);
        $request->session()->forget('privileged_reauthenticated_at');
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
