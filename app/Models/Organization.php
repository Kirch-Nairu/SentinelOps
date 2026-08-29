<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = ['public_id', 'name', 'slug'];

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }
}
