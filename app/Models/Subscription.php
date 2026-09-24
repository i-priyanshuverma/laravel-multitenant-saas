<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $ends_at
 */
class Subscription extends Model
{
    use HasFactory, HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'type',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Scope a query to only include active or trialing subscriptions.
     *
     * @param  Builder<Subscription>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('stripe_status', ['active', 'trialing']);
    }

    public function isActive(): bool
    {
        return in_array($this->stripe_status, ['active', 'trialing'], true) &&
            (is_null($this->ends_at) || $this->ends_at->isFuture());
    }
}
