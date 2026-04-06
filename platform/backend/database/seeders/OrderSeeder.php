<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding orders...');

        // Get all stores
        $stores = Store::all();

        foreach ($stores as $store) {
            $this->command->info("  Creating orders for store: {$store->name}");
            
            // Get customers for this store
            $customers = Customer::where('store_id', $store->id)->get();
            
            if ($customers->isEmpty()) {
                $this->command->warn("  No customers found for store {$store->name}, skipping...");
                continue;
            }

            // Get products for this store
            $products = Product::where('store_id', $store->id)
                ->where('status', 'active')
                ->get();

            if ($products->isEmpty()) {
                $this->command->warn("  No products found for store {$store->name}, skipping...");
                continue;
            }

            // Create 15 orders per store with different statuses
            $orderStatuses = [
                'pending' => 2,      // 2 pending orders
                'confirmed' => 3,    // 3 confirmed orders
                'processing' => 2,   // 2 processing orders
                'shipped' => 3,      // 3 shipped orders
                'delivered' => 4,    // 4 delivered orders
                'cancelled' => 1,    // 1 cancelled order
            ];

            foreach ($orderStatuses as $status => $count) {
                for ($i = 0; $i < $count; $i++) {
                    // Pick a random customer
                    $customer = $customers->random();

                    // Create order
                    $order = Order::factory()->create([
                        'store_id' => $store->id,
                        'customer_id' => $customer->id,
                        'status' => $status,
                        'payment_status' => in_array($status, ['delivered', 'shipped', 'processing']) ? 'paid' : 'pending',
                        'fulfillment_status' => match($status) {
                            'delivered' => 'fulfilled',
                            'shipped', 'processing' => 'partial',
                            default => 'unfulfilled',
                        },
                    ]);

                    // Add 1-4 items to the order
                    $itemCount = rand(1, 4);
                    $subtotal = 0;

                    for ($j = 0; $j < $itemCount; $j++) {
                        $product = $products->random();
                        $quantity = rand(1, 3);
                        $price = $product->price;
                        $discountAmount = rand(0, 1) ? rand(5, 20) : 0;
                        $taxAmount = ($price * $quantity) * 0.1;
                        $total = ($price * $quantity) - $discountAmount + $taxAmount;

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'price' => $price,
                            'discount_amount' => $discountAmount,
                            'tax_amount' => $taxAmount,
                            'total' => $total,
                            'product_snapshot' => [
                                'id' => $product->id,
                                'name' => $product->name,
                                'sku' => $product->sku,
                                'description' => $product->description,
                                'image_url' => $product->primary_image_url ?? null,
                            ],
                        ]);

                        $subtotal += ($price * $quantity);
                    }

                    // Update order totals
                    $shippingAmount = rand(5, 25);
                    $discountAmount = rand(0, 1) ? rand(10, 50) : 0;
                    $taxAmount = $subtotal * 0.1;
                    $total = $subtotal - $discountAmount + $shippingAmount + $taxAmount;

                    $order->update([
                        'subtotal' => $subtotal,
                        'discount_amount' => $discountAmount,
                        'shipping_amount' => $shippingAmount,
                        'tax_amount' => $taxAmount,
                        'total' => $total,
                    ]);

                    // Create payment record for paid orders
                    if ($order->payment_status === 'paid') {
                        Payment::factory()->manual()->completed()->create([
                            'store_id' => $store->id,
                            'order_id' => $order->id,
                            'amount' => $order->total,
                        ]);
                    }
                }
            }

            $this->command->info("  ✓ Created 15 orders with items and payments for {$store->name}");
        }

        $totalOrders = Order::count();
        $totalItems = OrderItem::count();
        $totalPayments = Payment::count();

        $this->command->info("✓ Orders seeded successfully!");
        $this->command->info("  Total: {$totalOrders} orders, {$totalItems} items, {$totalPayments} payments");
    }
}
