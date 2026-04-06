<?php

namespace App\Policies;

use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine if the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view customers');
    }

    /**
     * Determine if the user can view the customer.
     */
    public function view(User $user, $customer): bool
    {
        return $user->stores->contains($customer->store_id) && $user->hasPermissionTo('view customers');
    }

    /**
     * Determine if the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create customers');
    }

    /**
     * Determine if the user can update the customer.
     */
    public function update(User $user, $customer): bool
    {
        return $user->stores->contains($customer->store_id) && $user->hasPermissionTo('edit customers');
    }

    /**
     * Determine if the user can delete the customer.
     */
    public function delete(User $user, $customer): bool
    {
        return $user->stores->contains($customer->store_id) && $user->hasPermissionTo('delete customers');
    }
}
