<?php

namespace App\Models;

use App\Models\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class TenantModel extends Model
{
    use HasTenantScope;

    protected static function booted(): void
    {
        //
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
