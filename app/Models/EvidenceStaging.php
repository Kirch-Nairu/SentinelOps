<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EvidenceStaging extends Model
{
    protected $table = 'evidence_staging';
    protected $fillable = ['token','organization_id','uploaded_by_user_id','storage_key','original_name','mime_type','size_bytes','sha256','expires_at','attached_at'];
    protected function casts(): array { return ['expires_at'=>'immutable_datetime','attached_at'=>'immutable_datetime','size_bytes'=>'integer']; }
}
