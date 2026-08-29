<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OperationalEvent extends Model
{
    public $timestamps = false;
    protected $fillable = ['organization_id','event_type','severity','message','context','created_at'];
    protected function casts(): array { return ['context'=>'array','created_at'=>'immutable_datetime']; }
}
