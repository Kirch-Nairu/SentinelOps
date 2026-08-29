<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Incident extends Model
{
    protected $fillable = ['public_id','organization_id','asset_id','created_by_user_id','closed_by_user_id','severity','finding','status','created_offline','asset_revision_at_creation','revision','closed_at'];
    protected function casts(): array { return ['created_offline'=>'boolean','closed_at'=>'immutable_datetime','revision'=>'integer','asset_revision_at_creation'=>'integer']; }
    public function getRouteKeyName(): string { return 'public_id'; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function evidence(): HasMany { return $this->hasMany(Evidence::class); }
}
