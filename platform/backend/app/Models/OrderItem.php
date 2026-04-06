<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'discount_amount',
        'tax_amount',
        'total',
        'product_snapshot',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'product_snapshot' => 'array',
    ];

    /**
     * Boot model
     */
    protected static function booted()
    {
        // Auto-calculate total when creating/updating
        static::saving(function ($item) {
            if (!$item->total || $item->isDirty(['quantity', 'price', 'discount_amount', 'tax_amount'])) {
                $item->total = ($item->price * $item->quantity) - $item->discount_amount + $item->tax_amount;
            }
            
            // Capture product snapshot if not provided
            if (!$item->product_snapshot && $item->product) {
                $item->product_snapshot = [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'description' => $item->product->description,
                    'image_url' => $item->product->primary_image_url,
                ];
            }
        });
    }

    /**
     * Relationship to order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship to product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship to variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get product name from snapshot or relation
     */
    public function getProductNameAttribute(): string
    {
        return $this->product_snapshot['name'] ?? $this->product?->name ?? 'Unknown Product';
    }

    /**
     * Get product SKU from snapshot or relation
     */
    public function getProductSkuAttribute(): ?string
    {
        return $this->product_snapshot['sku'] ?? $this->product?->sku;
    }

    /**
     * Get line total (price * quantity)
     */
    public function getLineTotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2);
    }
}
