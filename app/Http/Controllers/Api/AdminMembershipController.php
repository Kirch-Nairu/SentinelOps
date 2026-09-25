<?php
namespace App\Http\Controllers\Api;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Identity\Authorization;
use App\Domain\Shared\CurrentOrganization;
use App\Domain\Shared\Role;
use App\Http\Controllers\Controller;
use App\Models\OrganizationMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class AdminMembershipController extends Controller
{
    public function update(Request $request, int $membership, CurrentOrganization $current, AuditRecorder $audit): JsonResponse
    {
        $data=$request->validate(['role'=>['required','string',Rule::enum(Role::class)]]);
        $org=$current->resolve($request->user());
        abort_unless(Authorization::hasAnyRole($request->user(),$org->id,[Role::Administrator]),403);
        $updated=DB::transaction(function() use($membership,$org,$data,$request,$audit){
            $target=OrganizationMembership::where('organization_id',$org->id)->whereKey($membership)->lockForUpdate()->firstOrFail();
            $new=Role::from($data['role']);
            if($target->role===Role::Administrator && $new!==Role::Administrator){
                $admins=OrganizationMembership::where('organization_id',$org->id)->where('is_active',true)->where('role',Role::Administrator->value)->lockForUpdate()->count();
                if($admins<=1) throw ValidationException::withMessages(['role'=>'The final active administrator cannot be demoted.']);
            }
            $old=$target->role->value;
            $target->role=$new; $target->save();
            $audit->record($org,$request->user(),'membership.role.changed','organization_membership',$target->id,['user_id'=>$target->user_id,'old_role'=>$old,'new_role'=>$new->value]);
            return $target;
        });
        return response()->json(['membership'=>['id'=>$updated->id,'user_id'=>$updated->user_id,'role'=>$updated->role->value]]);
    }
}
