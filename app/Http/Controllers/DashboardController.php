<?php
namespace App\Http\Controllers;
use App\Domain\Shared\CurrentOrganization;
use App\Models\Asset;
use App\Models\Incident;
use App\Models\OperationalEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class DashboardController extends Controller
{
    public function __invoke(Request $request, CurrentOrganization $current): Response
    {
        $org = $current->resolve($request->user());
        return Inertia::render('Dashboard', [
            'metrics'=>[
                'assets'=>Asset::where('organization_id',$org->id)->count(),
                'damaged_assets'=>Asset::where('organization_id',$org->id)->whereIn('status',['damaged','maintenance'])->count(),
                'open_incidents'=>Incident::where('organization_id',$org->id)->where('status','!=','closed')->count(),
            ],
            'events'=>OperationalEvent::where('organization_id',$org->id)->latest('created_at')->limit(12)->get(['event_type','severity','message','context','created_at']),
            'incidents'=>Incident::where('organization_id',$org->id)->with('asset:id,public_id,code,name')->latest()->limit(10)->get(['id','public_id','asset_id','severity','finding','status','created_offline','created_at']),
        ]);
    }
}
