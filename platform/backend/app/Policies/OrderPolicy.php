<?php

namespace App\Policies;

use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view orders');
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, $order): bool
    {
        return $user->stores->contains($order->store_id) && $user->hasPermissionTo('view orders');
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create orders');
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, $order): bool
    {
        return $user->stores->contains($order->store_id) && $user->hasPermissionTo('edit orders');
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, $order): bool
    {
        return $user->stores->contains($order->store_id) && $user->hasPermissionTo('delete orders');
    }

    /**
     * Determine if the user can process the order (mark as paid, ship, etc.).
     */
    public function process(User $user, $order): bool
    {
        return $user->stores->contains($order->store_id) && $user->hasPermissionTo('process orders');
    }

    /**
     * Determine if the user can cancel the order.
     */
    public function cancel(User $user, $order): bool
    {
        return $user->stores->contains($order->store_id) && $user->hasPermissionTo('cancel orders');
    }

    /**
     * Determine if the user can refund the order.
     */
    public function refund(User $user, $order): bool
    {
        return $user->stores->contains($order->store_id) && $user->hasPermissionTo('refund orders');
    }
}
