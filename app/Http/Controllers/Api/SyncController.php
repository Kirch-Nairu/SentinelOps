<?php

namespace App\Http\Controllers\Api;

use App\Domain\Shared\CurrentOrganization;
use App\Domain\Sync\SyncOperationExecutor;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class SyncController extends Controller
{
    public function store(Request $request, CurrentOrganization $current, SyncOperationExecutor $executor): JsonResponse
    {
        if ($request->has('organization_id')) {
            return response()->json(['message'=>'organization_id is server-derived and must not be supplied.','code'=>'UNTRUSTED_TENANT_IDENTIFIER'], 422);
        }

        $validated = $request->validate([
            'operations' => ['required','array','min:1','max:50'],
            'operations.*.client_operation_id' => ['required','uuid'],
            'operations.*.client_sequence' => ['nullable','integer','min:0'],
            'operations.*.type' => ['required','string','in:asset.assign,incident.create'],
            'operations.*.payload' => ['required','array'],
        ]);

        $organization = $current->resolve($request->user());
        $operations = Arr::sort($validated['operations'], fn (array $op) => (int) ($op['client_sequence'] ?? 0));
        $results = [];
        foreach ($operations as $operation) {
            try {
                $results[] = $executor->execute($request->user(), $organization, $operation);
            } catch (Throwable $e) {
                report($e);
                $results[] = [
                    'client_operation_id' => $operation['client_operation_id'],
                    'status' => 'retryable_error',
                    'code' => 'SERVER_ERROR_RETRYABLE',
                    'reconciliation' => null,
                ];
            }
        }
        return response()->json(['results'=>$results]);
    }
}
