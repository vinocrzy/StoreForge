<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'store_id',
        'type',
        'label',
        'first_name',
        'last_name',
        'company',
        'address_line1',
        'address_line2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'phone',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Boot model and apply global scope for tenant isolation
     */
    protected static function booted()
    {
        // CRITICAL: Automatically filter all queries by current store
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant() && tenant()->exists()) {
                $builder->where('customer_addresses.store_id', tenant()->id);
            }
        });

        // CRITICAL: Automatically set store_id when creating
        static::creating(function ($model) {
            if (!$model->store_id && tenant() && tenant()->exists()) {
                $model->store_id = tenant()->id;
            }
        });

        // When setting an address as default, unset others
        static::saving(function ($model) {
            if ($model->is_default && $model->isDirty('is_default')) {
                static::where('customer_id', $model->customer_id)
                    ->where('type', $model->type)
                    ->where('id', '!=', $model->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Relationship to customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship to store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get full address as single string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state_province,
            $this->postal_code,
            $this->country,
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if this is a shipping address
     */
    public function isShipping(): bool
    {
        return in_array($this->type, ['shipping', 'both']);
    }

    /**
     * Check if this is a billing address
     */
    public function isBilling(): bool
    {
        return in_array($this->type, ['billing', 'both']);
    }

    /**
     * Scope: Only shipping addresses
     */
    public function scopeShipping(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->where('type', 'shipping')->orWhere('type', 'both');
        });
    }

    /**
     * Scope: Only billing addresses
     */
    public function scopeBilling(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->where('type', 'billing')->orWhere('type', 'both');
        });
    }

    /**
     * Scope: Only default addresses
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Query builder without tenant scope (use with EXTREME caution)
     */
    public static function withoutTenancy()
    {
        return static::withoutGlobalScope('store');
    }
}
