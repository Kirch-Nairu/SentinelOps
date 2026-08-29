<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    protected $fillable = ['public_id','organization_id','location_id','code','name','status','revision'];

    protected function casts(): array { return ['revision' => 'integer']; }

    public function getRouteKeyName(): string { return 'public_id'; }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function assignments(): HasMany { return $this->hasMany(AssetAssignment::class); }
    public function activeAssignment(): HasOne { return $this->hasOne(AssetAssignment::class)->whereNull('ended_at')->latestOfMany('started_at'); }
    public function incidents(): HasMany { return $this->hasMany(Incident::class); }
}
