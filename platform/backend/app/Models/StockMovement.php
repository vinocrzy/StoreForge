<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Only track created_at

    protected $fillable = [
        'store_id',
        'inventory_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reference_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Boot model and apply global scope for tenant isolation
     */
    protected static function booted()
    {
        // CRITICAL: Automatically filter all queries by current store
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant() && tenant()->exists()) {
                $builder->where('store_id', tenant()->id);
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
     * Relationship to inventory
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Relationship to user (who made the movement)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get polymorphic reference
     */
    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            $class = 'App\\Models\\' . $this->reference_type;
            if (class_exists($class)) {
                return $class::find($this->reference_id);
            }
        }
        return null;
    }

    /**
     * Scope: Filter by movement type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by reference
     */
    public function scopeForReference(Builder $query, string $type, int $id): Builder
    {
        return $query->where('reference_type', $type)
            ->where('reference_id', $id);
    }

    /**
     * Check if movement is positive (adds stock)
     */
    public function isPositive(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Check if movement is negative (removes stock)
     */
    public function isNegative(): bool
    {
        return $this->quantity < 0;
    }
}
