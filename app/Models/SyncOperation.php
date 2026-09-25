<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SyncOperation extends Model
{
    protected $fillable = ['organization_id','user_id','client_operation_id','client_sequence','operation_type','payload_hash','status','rejection_code','result','executed_at'];
    protected function casts(): array { return ['result'=>'array','executed_at'=>'immutable_datetime','client_sequence'=>'integer']; }
}
