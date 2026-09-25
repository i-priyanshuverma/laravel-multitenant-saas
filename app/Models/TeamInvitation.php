<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $expires_at
 */
class TeamInvitation extends Model
{
    use HasFactory, HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'email',
        'role',
        'token',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    /**
     * Scope a query to only include pending (non-expired) invitations.
     *
     * @param  Builder<TeamInvitation>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }
}
