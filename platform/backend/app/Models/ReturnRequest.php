<?php

namespace App\Models;

use App\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnRequest extends TenantModel
{
    use SoftDeletes;

    protected $table = 'returns';

    protected $fillable = [
        'store_id',
        'order_id',
        'customer_id',
        'return_number',
        'reason',
        'reason_details',
        'status',
        'refund_amount',
        'admin_notes',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (ReturnRequest $model) {
            if (empty($model->return_number)) {
                $model->return_number = 'RET-' . ($model->store_id ?? tenant()->id) . '-' . time();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
