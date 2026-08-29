<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AssetAssignment extends Model
{
    protected $fillable = ['organization_id','asset_id','assignee_user_id','assigned_by_user_id','ended_by_user_id','reason','started_at','ended_at'];
    protected function casts(): array { return ['started_at'=>'immutable_datetime','ended_at'=>'immutable_datetime']; }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assignee_user_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
}
