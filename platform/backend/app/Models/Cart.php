<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Cart extends Model
{
    protected $fillable = [
        'store_id',
        'customer_id',
        'token',
        'items',
        'expires_at',
    ];

    protected $casts = [
        'items'      => 'array',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant() && tenant()->exists()) {
                $builder->where('carts.store_id', tenant()->id);
            }
        });

        static::creating(function (Cart $cart) {
            if (!$cart->store_id && tenant() && tenant()->exists()) {
                $cart->store_id = tenant()->id;
            }
            if (!$cart->token) {
                $cart->token = Str::random(48);
            }
            if (!$cart->expires_at) {
                $cart->expires_at = now()->addDays(30);
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
