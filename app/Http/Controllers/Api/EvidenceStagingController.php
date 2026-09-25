<?php

namespace App\Http\Controllers\Api;

use App\Domain\Identity\Authorization;
use App\Domain\Shared\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\EvidenceStaging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EvidenceStagingController extends Controller
{
    public function store(Request $request, CurrentOrganization $current): JsonResponse
    {
        $request->validate(['evidence'=>['required','file','mimes:jpg,jpeg,png,webp','max:10240']]);
        $organization = $current->resolve($request->user());
        if (! Authorization::membership($request->user(), $organization->id)) abort(403);

        $file = $request->file('evidence');
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false || ! in_array($imageInfo['mime'] ?? '', ['image/jpeg','image/png','image/webp'], true)) {
            throw ValidationException::withMessages(['evidence'=>'Evidence must be a decodable JPEG, PNG, or WebP image.']);
        }

        $token = (string) Str::uuid();
        $objectName = (string) Str::uuid();
        $key = Storage::disk('local')->putFileAs('sentinelops/evidence/objects', $file, $objectName);
        if (! $key) throw ValidationException::withMessages(['evidence'=>'Evidence storage failed.']);

        try {
            $path = Storage::disk('local')->path($key);
            $row = EvidenceStaging::query()->create([
                'token'=>$token,
                'organization_id'=>$organization->id,
                'uploaded_by_user_id'=>$request->user()->id,
                'storage_key'=>$key,
                'original_name'=>mb_substr($file->getClientOriginalName(),0,255),
                'mime_type'=>$imageInfo['mime'],
                'size_bytes'=>$file->getSize(),
                'sha256'=>hash_file('sha256',$path),
                'expires_at'=>now()->addDay(),
            ]);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($key);
            throw $e;
        }

        return response()->json(['token'=>$row->token,'sha256'=>$row->sha256,'size_bytes'=>$row->size_bytes,'expires_at'=>$row->expires_at->toIso8601String()], 201);
    }
}
