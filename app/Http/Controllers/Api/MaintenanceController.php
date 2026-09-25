<?php
namespace App\Http\Controllers\Api;
use App\Domain\Maintenance\CreateMaintenance;
use App\Domain\Shared\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MaintenanceController extends Controller
{
    public function store(Request $request, string $asset, CurrentOrganization $current, CreateMaintenance $create): JsonResponse
    {
        $data=$request->validate(['description'=>['required','string','max:5000'],'incident_public_id'=>['nullable','uuid']]);
        $org=$current->resolve($request->user());
        $assetModel=Asset::where('organization_id',$org->id)->where('public_id',$asset)->firstOrFail();
        $incident=!empty($data['incident_public_id'])?Incident::where('organization_id',$org->id)->where('public_id',$data['incident_public_id'])->firstOrFail():null;
        $record=DB::transaction(fn()=> $create->execute($request->user(),$org,$assetModel,$incident,$data['description']));
        return response()->json(['maintenance'=>['public_id'=>$record->public_id,'status'=>$record->status]],201);
    }
}
