<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    protected $fillable = [
        'store_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'status',
        'date_of_birth',
        'gender',
        'notes',
        'metadata',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot model and apply global scope for tenant isolation
     */
    protected static function booted()
    {
        // CRITICAL: Automatically filter all queries by current store
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant() && tenant()->exists()) {
                $builder->where('customers.store_id', tenant()->id);
            }
        });

        // CRITICAL: Automatically set store_id when creating
        static::creating(function ($model) {
            if (!$model->store_id && tenant() && tenant()->exists()) {
                $model->store_id = tenant()->id;
            }
        });
    }

    /**
     * Relationship to store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Customer addresses
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Default shipping address
     */
    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)
            ->where('is_default', true)
            ->where(function($q) {
                $q->where('type', 'shipping')->orWhere('type', 'both');
            });
    }

    /**
     * Shipping addresses
     */
    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)
            ->where(function($q) {
                $q->where('type', 'shipping')->orWhere('type', 'both');
            });
    }

    /**
     * Billing addresses
     */
    public function billingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)
            ->where(function($q) {
                $q->where('type', 'billing')->orWhere('type', 'both');
            });
    }

    /**
     * Get customer's full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if customer is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if customer is banned
     */
    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    /**
     * Check if email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if phone is verified
     */
    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(): void
    {
        $this->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }

    /**
     * Mark phone as verified
     */
    public function markPhoneAsVerified(): void
    {
        $this->forceFill([
            'phone_verified_at' => now(),
        ])->save();
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Scope: Only active customers
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Search customers
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Query builder without tenant scope (use with EXTREME caution)
     */
    public static function withoutTenancy()
    {
        return static::withoutGlobalScope('store');
    }
}
