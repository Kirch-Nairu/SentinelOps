<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class RequirePrivilegedReauthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $at = (int) $request->session()->get('privileged_reauthenticated_at', 0);
        if ($at <= 0 || now()->timestamp - $at > 900) {
            return $request->expectsJson()
                ? response()->json(['message'=>'Privileged reauthentication required.','code'=>'STEP_UP_REQUIRED'], 403)
                : redirect()->route('dashboard')->with('error', 'Privileged reauthentication required.');
        }
        return $next($request);
    }
}
