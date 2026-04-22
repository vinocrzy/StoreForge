<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Order;
use App\Observers\CustomerNotificationObserver;
use App\Observers\OrderNotificationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderNotificationObserver::class);
        Customer::observe(CustomerNotificationObserver::class);
    }
}
