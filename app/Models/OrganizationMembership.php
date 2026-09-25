<?php

namespace App\Models;

use App\Domain\Shared\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMembership extends Model
{
    protected $fillable = ['organization_id', 'user_id', 'role', 'is_active'];

    protected function casts(): array
    {
        return ['role' => Role::class, 'is_active' => 'boolean'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
