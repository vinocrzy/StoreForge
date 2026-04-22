<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Store;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderShippedNotification;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Support\Facades\Log;

class OrderNotificationObserver
{
    /**
     * Handle the Order "created" event.
     * Send order confirmation email to the customer.
     */
    public function created(Order $order): void
    {
        $this->sendNotification($order, 'order_confirmation');
    }

    /**
     * Handle the Order "updated" event.
     * Detect status changes and send appropriate notifications.
     */
    public function updated(Order $order): void
    {
        // Payment status changed to paid
        if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
            $this->sendNotification($order, 'payment_received');
        }

        // Order status changed to shipped
        if ($order->wasChanged('status') && $order->status === 'shipped') {
            $this->sendNotification($order, 'order_shipped');
        }

        // Order status changed to delivered
        if ($order->wasChanged('status') && $order->status === 'delivered') {
            $this->sendNotification($order, 'order_delivered');
        }
    }

    /**
     * Send the appropriate notification to the customer.
     */
    private function sendNotification(Order $order, string $type): void
    {
        $customer = $order->customer;

        if (!$customer) {
            Log::info("Skipping {$type} email: no customer on order #{$order->order_number}");
            return;
        }

        if (empty($customer->email)) {
            Log::info("Skipping {$type} email: customer #{$customer->id} has no email address on order #{$order->order_number}");
            return;
        }

        $store = $order->store ?? Store::find($order->store_id);

        if (!$store) {
            Log::warning("Skipping {$type} email: no store found for order #{$order->order_number}");
            return;
        }

        $notification = match ($type) {
            'order_confirmation' => new OrderConfirmationNotification($order, $store),
            'payment_received' => new PaymentReceivedNotification($order, $store),
            'order_shipped' => new OrderShippedNotification($order, $store),
            'order_delivered' => new OrderDeliveredNotification($order, $store),
            default => null,
        };

        if ($notification) {
            $customer->notify($notification);
            Log::info("Queued {$type} email for customer #{$customer->id} on order #{$order->order_number}");
        }
    }
}
