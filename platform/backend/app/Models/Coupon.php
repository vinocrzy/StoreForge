<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'code',
        'type',
        'value',
        'status',
        'usage_limit',
        'used_count',
        'usage_limit_per_customer',
        'minimum_purchase_amount',
        'maximum_discount_amount',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_purchase_amount' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'usage_limit_per_customer' => 'integer',
    ];

    /**
     * Boot model and apply global scope for tenant isolation.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant() && tenant()->exists()) {
                $builder->where('coupons.store_id', tenant()->id);
            }
        });

        static::creating(function (Coupon $model) {
            if (!$model->store_id && tenant() && tenant()->exists()) {
                $model->store_id = tenant()->id;
            }
        });
    }

    /**
     * Relationship to store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Coupon usage records.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Scope: active coupons only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: valid coupons (active + within date range + usage limit not reached).
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('usage_limit')
                  ->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }
}
