<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'domain',
        'is_primary',
        'is_fallback',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_fallback' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }
}
