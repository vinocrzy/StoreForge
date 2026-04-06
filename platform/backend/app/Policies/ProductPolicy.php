<?php

namespace App\Policies;

use App\Models\User;

class ProductPolicy
{
    /**
     * Determine if the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view products');
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User $user, $product): bool
    {
        // Check if user has access to product's store
        return $user->stores->contains($product->store_id) && $user->hasPermissionTo('view products');
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create products');
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User $user, $product): bool
    {
        return $user->stores->contains($product->store_id) && $user->hasPermissionTo('edit products');
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user, $product): bool
    {
        return $user->stores->contains($product->store_id) && $user->hasPermissionTo('delete products');
    }

    /**
     * Determine if the user can manage inventory.
     */
    public function manageInventory(User $user, $product): bool
    {
        return $user->stores->contains($product->store_id) && $user->hasPermissionTo('manage inventory');
    }
}
