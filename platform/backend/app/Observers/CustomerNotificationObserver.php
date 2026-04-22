<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\Store;
use App\Notifications\WelcomeCustomerNotification;
use Illuminate\Support\Facades\Log;

class CustomerNotificationObserver
{
    /**
     * Handle the Customer "created" event.
     * Send welcome email to the new customer.
     */
    public function created(Customer $customer): void
    {
        if (empty($customer->email)) {
            Log::info("Skipping welcome email: customer #{$customer->id} has no email address");
            return;
        }

        $store = $customer->store ?? Store::find($customer->store_id);

        if (!$store) {
            Log::warning("Skipping welcome email: no store found for customer #{$customer->id}");
            return;
        }

        $customer->notify(new WelcomeCustomerNotification($store));
        Log::info("Queued welcome email for customer #{$customer->id}");
    }
}
