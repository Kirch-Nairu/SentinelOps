<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditEvent extends Model
{
    public $timestamps = false;
    protected $fillable = ['event_id','organization_id','actor_user_id','client_operation_id','event_type','subject_type','subject_id','data','created_at'];
    protected function casts(): array { return ['data'=>'array','created_at'=>'immutable_datetime']; }
    public function save(array $options = []): bool
    {
        if ($this->exists && $this->isDirty()) {
            throw new \LogicException('Audit events are append-only.');
        }
        return parent::save($options);
    }
    public function delete(): ?bool { throw new \LogicException('Audit events are append-only.'); }
}
