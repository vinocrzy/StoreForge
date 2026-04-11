<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_id',
        'file_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    protected $appends = [
        'url',
    ];

    /**
     * Boot model and apply global scope for tenant isolation
     */
    protected static function booted()
    {
        // CRITICAL: Automatically filter all queries by current store
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant() && tenant()->exists()) {
                $builder->where('product_images.store_id', tenant()->id);
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
     * Relationship to product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship to store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the full URL for the image
     */
    public function getUrlAttribute(): string
    {
        if (!$this->file_path) {
            return '';
        }

        // Support external CDN URLs (Pexels, Unsplash, S3, etc.)
        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        // Return URL relative to storage public disk
        return url('storage/' . $this->file_path);
    }

    /**
     * Query builder without tenant scope (use with EXTREME caution)
     */
    public static function withoutTenancy()
    {
        return static::withoutGlobalScope('store');
    }
}
