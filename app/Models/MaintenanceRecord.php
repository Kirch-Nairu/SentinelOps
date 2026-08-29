<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MaintenanceRecord extends Model
{
    protected $fillable = ['public_id','organization_id','asset_id','incident_id','opened_by_user_id','assigned_to_user_id','completed_by_user_id','description','status','completed_at'];
    protected function casts(): array { return ['completed_at'=>'immutable_datetime']; }
    public function getRouteKeyName(): string { return 'public_id'; }
}
