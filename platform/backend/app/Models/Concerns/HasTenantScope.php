<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasTenantScope
{
    protected static function bootHasTenantScope(): void
    {
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant()->exists()) {
                $builder->where($builder->getQuery()->from . '.store_id', tenant()->id);
            }
        });

        static::creating(function (Model $model) {
            if (tenant()->exists() && !$model->store_id) {
                $model->store_id = tenant()->id;
            }
        });
    }

    public function belongsToCurrentTenant(): bool
    {
        return $this->store_id === tenant()->id;
    }

    public function belongsToStore(int $storeId): bool
    {
        return $this->store_id === $storeId;
    }

    public function scopeWithoutTenancy(Builder $query): Builder
    {
        return $query->withoutGlobalScope('store');
    }
}
