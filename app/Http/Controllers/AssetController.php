<?php
namespace App\Http\Controllers;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Identity\Authorization;
use App\Domain\Shared\CurrentOrganization;
use App\Domain\Shared\Role;
use App\Models\Asset;
use App\Models\Location;
use App\Models\OrganizationMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
class AssetController extends Controller
{
    public function index(Request $request, CurrentOrganization $current): Response
    {
        $org=$current->resolve($request->user());
        abort_unless(Authorization::membership($request->user(),$org->id),403);
        return Inertia::render('Assets/Index',['assets'=>Asset::where('organization_id',$org->id)->with('location:id,name')->orderBy('code')->get(['id','public_id','location_id','code','name','status','revision'])]);
    }
    public function show(Request $request, string $asset, CurrentOrganization $current): Response
    {
        $org=$current->resolve($request->user());
        $record=Asset::where('organization_id',$org->id)->where('public_id',$asset)->with(['location:id,code,name','activeAssignment.assignee:id,name,email','incidents'=>fn($q)=>$q->latest()->limit(15),'incidents.evidence'])->firstOrFail();
        $this->authorize('view',$record);
        return Inertia::render('Assets/Show',[
            'asset'=>$record,
            'assignable_users'=>OrganizationMembership::where('organization_id',$org->id)->where('is_active',true)->with('user:id,name,email')->get()->map(fn($m)=>['id'=>$m->user_id,'name'=>$m->user->name,'role'=>$m->role->value]),
        ]);
    }
    public function store(Request $request, CurrentOrganization $current, AuditRecorder $audit): RedirectResponse
    {
        $org=$current->resolve($request->user());
        abort_unless(Authorization::hasAnyRole($request->user(),$org->id,[Role::Administrator,Role::Supervisor]),403);
        $data=$request->validate(['code'=>['required','string','max:96'],'name'=>['required','string','max:255'],'location_id'=>['nullable','integer']]);
        if (!empty($data['location_id']) && !Location::where('organization_id',$org->id)->whereKey($data['location_id'])->exists()) abort(422);
        $asset=DB::transaction(function() use($org,$data,$request,$audit){ $asset=Asset::create(['public_id'=>(string)Str::uuid(),'organization_id'=>$org->id,'location_id'=>$data['location_id']??null,'code'=>$data['code'],'name'=>$data['name'],'status'=>'available','revision'=>1]); $audit->record($org,$request->user(),'asset.created','asset',$asset->public_id,['code'=>$asset->code,'revision'=>$asset->revision]); return $asset; });
        return redirect()->route('assets.show',$asset->public_id);
    }
}
