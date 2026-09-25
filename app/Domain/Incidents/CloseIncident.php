<?php
namespace App\Domain\Incidents;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Identity\Authorization;
use App\Domain\Shared\Role;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
final class CloseIncident
{
    public function __construct(private readonly AuditRecorder $audit) {}
    public function execute(User $actor, Organization $organization, Incident $incident): Incident
    {
        if ($incident->organization_id !== $organization->id) throw new AuthorizationException();
        if (! Authorization::hasAnyRole($actor, $organization->id, [Role::Administrator,Role::Supervisor,Role::SecurityOfficer])) throw new AuthorizationException();
        $incident = Incident::query()->whereKey($incident->id)->lockForUpdate()->firstOrFail();
        if ($incident->status === 'closed') throw ValidationException::withMessages(['incident'=>'Incident is already closed.']);
        $incident->forceFill(['status'=>'closed','closed_at'=>now(),'closed_by_user_id'=>$actor->id,'revision'=>$incident->revision+1])->save();
        $this->audit->record($organization,$actor,'incident.closed','incident',$incident->public_id,['revision'=>$incident->revision]);
        return $incident;
    }
}
