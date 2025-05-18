<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    use HasFactory, HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'stripe_payment_method_id',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
