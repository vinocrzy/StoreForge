<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Append image_url to JSON so storefront types are satisfied.
     */
    protected $appends = ['image_url'];

    /**
     * Maps the `image` DB column to the `image_url` attribute expected by the storefront.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->attributes['image'] ?? null;
    }

    /**
     * Boot model and apply global scope for tenant isolation
     */
    protected static function booted()
    {
        // CRITICAL: Automatically filter all queries by current store
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant() && tenant()->exists()) {
                $builder->where('categories.store_id', tenant()->id);
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
     * Parent category (for hierarchy)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Products in this category (many-to-many)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    /**
     * Check if category is root (no parent)
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants()
    {
        return $this->children()->with('children');
    }

    /**
     * Scope: Only active categories
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Root categories only
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    /**
     * Query builder without tenant scope (use with EXTREME caution)
     */
    public static function withoutTenancy()
    {
        return static::withoutGlobalScope('store');
    }
}
