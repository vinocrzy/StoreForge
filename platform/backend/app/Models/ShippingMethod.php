<?php

namespace App\Models;

use App\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingMethod extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'type',
        'rate',
        'free_above',
        'config',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'rate'          => 'decimal:2',
        'free_above'    => 'decimal:2',
        'config'        => 'array',
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
