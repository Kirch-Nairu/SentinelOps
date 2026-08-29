<?php
namespace App\Http\Controllers\Api;
use App\Domain\Incidents\CloseIncident;
use App\Domain\Shared\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class IncidentController extends Controller
{
    public function close(Request $request, string $incident, CurrentOrganization $current, CloseIncident $close): JsonResponse
    {
        $org=$current->resolve($request->user());
        $record=Incident::where('organization_id',$org->id)->where('public_id',$incident)->firstOrFail();
        $closed=DB::transaction(fn()=> $close->execute($request->user(),$org,$record));
        return response()->json(['incident'=>['public_id'=>$closed->public_id,'status'=>$closed->status,'revision'=>$closed->revision,'closed_at'=>$closed->closed_at?->toIso8601String()]]);
    }
}
