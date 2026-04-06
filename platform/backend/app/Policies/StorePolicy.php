<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    /**
     * Determine if the user can view any stores.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view stores');
    }

    /**
     * Determine if the user can view the store.
     */
    public function view(User $user, Store $store): bool
    {
        // User must have access to this store
        return $user->stores->contains($store->id) && $user->hasPermissionTo('view stores');
    }

    /**
     * Determine if the user can create stores.
     */
    public function create(User $user): bool
    {
        // Only super admin can create stores
        return $user->hasRole('super-admin');
    }

    /**
     * Determine if the user can update the store.
     */
    public function update(User $user, Store $store): bool
    {
        return $user->stores->contains($store->id) && $user->hasPermissionTo('edit stores');
    }

    /**
     * Determine if the user can delete the store.
     */
    public function delete(User $user, Store $store): bool
    {
        return $user->stores->contains($store->id) && $user->hasPermissionTo('delete stores');
    }
}
