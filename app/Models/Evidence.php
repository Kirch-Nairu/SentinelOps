<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Evidence extends Model
{
    public $timestamps = false;
    protected $table = 'evidence';
    protected $fillable = ['public_id','organization_id','incident_id','uploaded_by_user_id','storage_key','original_name','mime_type','size_bytes','sha256','created_at'];
    protected function casts(): array { return ['created_at'=>'immutable_datetime','size_bytes'=>'integer']; }
    public function getRouteKeyName(): string { return 'public_id'; }
    public function incident(): BelongsTo { return $this->belongsTo(Incident::class); }
}
