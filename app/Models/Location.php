<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Location extends Model
{
    protected $fillable = ['organization_id','code','name'];
}
