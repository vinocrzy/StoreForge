<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyPoint extends Model
{
    use Concerns\HasTenantScope;

    protected $fillable = [
        'store_id',
        'customer_id',
        'points',
        'type',
        'description',
        'source_type',
        'source_id',
        'balance_after',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
