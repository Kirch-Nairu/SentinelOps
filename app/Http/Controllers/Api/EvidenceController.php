<?php
namespace App\Http\Controllers\Api;
use App\Domain\Shared\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\Evidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
class EvidenceController extends Controller
{
    public function show(Request $request, string $evidence, CurrentOrganization $current): StreamedResponse
    {
        $organization = $current->resolve($request->user());
        $record = Evidence::query()->with('incident.asset.activeAssignment')
            ->where('public_id',$evidence)->where('organization_id',$organization->id)->firstOrFail();
        $this->authorize('view', $record);
        abort_unless(Storage::disk('local')->exists($record->storage_key), 503, 'Evidence bytes are unavailable.');
        return Storage::disk('local')->download($record->storage_key, $record->original_name, ['Content-Type'=>$record->mime_type,'Cache-Control'=>'private, no-store']);
    }
}
