<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class ReauthenticationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['password'=>['required','string']]);
        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password'=>'Password confirmation failed.']);
        }
        $request->session()->put('privileged_reauthenticated_at', now()->timestamp);
        return response()->json(['reauthenticated_until'=>now()->addMinutes(15)->toIso8601String()]);
    }
}
