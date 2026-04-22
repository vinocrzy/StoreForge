<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    /**
     * No updated_at column — wishlists only track creation time.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'customer_id',
        'product_id',
    ];

    /**
     * Boot model and apply global scope for tenant isolation.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant() && tenant()->exists()) {
                $builder->where('wishlists.store_id', tenant()->id);
            }
        });

        static::creating(function (Wishlist $model) {
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
     * Relationship to customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship to product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
