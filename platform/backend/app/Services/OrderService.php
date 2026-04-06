<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Create a new order
     *
     * @param array $data Order data including items
     * @return Order
     * @throws \Exception
     */
    public function createOrder(array $data): Order
    {
        DB::beginTransaction();
        
        try {
            // Verify customer exists
            $customer = Customer::findOrFail($data['customer_id']);
            
            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => $data['status'] ?? 'pending',
                'payment_status' => $data['payment_status'] ?? 'pending',
                'fulfillment_status' => 'unfulfilled',
                'currency' => $data['currency'] ?? 'USD',
                'customer_note' => $data['customer_note'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'manual',
                'billing_address_id' => $data['billing_address_id'] ?? null,
                'shipping_address_id' => $data['shipping_address_id'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'coupon_code' => $data['coupon_code'] ?? null,
            ]);
            
            // Add items to order
            $subtotal = 0;
            $totalTax = 0;
            $totalDiscount = 0;
            
            foreach ($data['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                
                // Calculate item totals
                $quantity = $itemData['quantity'];
                $price = $itemData['price'] ?? $product->price;
                $discountAmount = $itemData['discount_amount'] ?? 0;
                $taxRate = $itemData['tax_rate'] ?? 0.1; // 10% default
                $taxAmount = ($price * $quantity - $discountAmount) * $taxRate;
                $total = ($price * $quantity) - $discountAmount + $taxAmount;
                
                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $itemData['variant_id'] ?? null,
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
                        'image_url' => $product->primary_image_url,
                    ],
                ]);
                
                $subtotal += ($price * $quantity);
                $totalTax += $taxAmount;
                $totalDiscount += $discountAmount;
            }
            
            // Calculate shipping
            $shippingAmount = $data['shipping_amount'] ?? $this->calculateShipping($order, $subtotal);
            
            // Calculate grand total
            $grandTotal = $subtotal - $totalDiscount + $shippingAmount + $totalTax;
            
            // Update order totals
            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => $totalTax,
                'total' => $grandTotal,
            ]);
            
            DB::commit();
            
            Log::info("Order created successfully", ['order_id' => $order->id, 'order_number' => $order->order_number]);
            
            return $order->load(['items', 'customer', 'payments']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create order", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Calculate shipping cost
     *
     * @param Order $order
     * @param float $subtotal
     * @return float
     */
    protected function calculateShipping(Order $order, float $subtotal): float
    {
        // Simple logic: free shipping over $100, otherwise $10
        return $subtotal >= 100 ? 0 : 10;
    }
    
    /**
     * Update order status
     *
     * @param Order $order
     * @param string $status
     * @return Order
     */
    public function updateOrderStatus(Order $order, string $status): Order
    {
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid order status: {$status}");
        }
        
        // Use status-specific methods for proper timestamp tracking
        switch ($status) {
            case 'confirmed':
                $order->markAsConfirmed();
                break;
            case 'processing':
                $order->markAsProcessing();
                break;
            case 'shipped':
                $order->markAsShipped();
                break;
            case 'delivered':
                $order->markAsDelivered();
                break;
            case 'cancelled':
                $order->markAsCancelled();
                $this->releaseInventory($order);
                break;
            default:
                $order->update(['status' => $status]);
        }
        
        Log::info("Order status updated", [
            'order_id' => $order->id,
            'old_status' => $order->getOriginal('status'),
            'new_status' => $status,
        ]);
        
        return $order->fresh();
    }
    
    /**
     * Record manual payment
     *
     * @param Order $order
     * @param array $paymentData
     * @return Payment
     */
    public function recordPayment(Order $order, array $paymentData): Payment
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $paymentData['gateway'] ?? 'manual',
            'payment_method' => $paymentData['payment_method'] ?? 'bank_transfer',
            'amount' => $paymentData['amount'] ?? $order->total,
            'currency' => $paymentData['currency'] ?? $order->currency,
            'status' => $paymentData['status'] ?? 'completed',
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'metadata' => $paymentData['metadata'] ?? null,
            'processed_at' => now(),
        ]);
        
        // Check if order is fully paid
        $totalPaid = $order->payments()->where('status', 'completed')->sum('amount');
        
        if ($totalPaid >= $order->total) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'paid_by_user_id' => $paymentData['paid_by_user_id'] ?? auth()->id(),
                'payment_notes' => $paymentData['payment_notes'] ?? null,
            ]);
        } elseif ($totalPaid > 0) {
            $order->update(['payment_status' => 'partially_paid']);
        }
        
        Log::info("Payment recorded", [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ]);
        
        return $payment;
    }
    
    /**
     * Fulfill order (adjust inventory)
     *
     * @param Order $order
     * @return void
     * @throws \Exception
     */
    public function fulfillOrder(Order $order): void
    {
        DB::beginTransaction();
        
        try {
            foreach ($order->items as $item) {
                // Find inventory for this product
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->where('available_quantity', '>=', $item->quantity)
                    ->first();
                
                if (!$inventory) {
                    throw new \Exception("Insufficient inventory for product: {$item->product->name}");
                }
                
                // Deduct from inventory
                $inventory->decrement('available_quantity', $item->quantity);
                $inventory->decrement('quantity_on_hand', $item->quantity);
                
                // Log stock movement
                $inventory->movements()->create([
                    'type' => 'sale',
                    'quantity' => -$item->quantity,
                    'reason' => "Order {$order->order_number} fulfilled",
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'quantity_before' => $inventory->quantity_on_hand + $item->quantity,
                    'quantity_after' => $inventory->quantity_on_hand,
                ]);
            }
            
            // Update order fulfillment status
            $order->update(['fulfillment_status' => 'fulfilled']);
            
            DB::commit();
            
            Log::info("Order fulfilled", ['order_id' => $order->id]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to fulfill order", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Release inventory when order is cancelled
     *
     * @param Order $order
     * @return void
     */
    protected function releaseInventory(Order $order): void
    {
        // Only release if order was fulfilled
        if ($order->fulfillment_status !== 'fulfilled') {
            return;
        }
        
        foreach ($order->items as $item) {
            $inventory = Inventory::where('product_id', $item->product_id)->first();
            
            if ($inventory) {
                $inventory->increment('available_quantity', $item->quantity);
                $inventory->increment('quantity_on_hand', $item->quantity);
                
                // Log stock movement
                $inventory->movements()->create([
                    'type' => 'adjustment',
                    'quantity' => $item->quantity,
                    'reason' => "Order {$order->order_number} cancelled - inventory released",
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'quantity_before' => $inventory->quantity_on_hand - $item->quantity,
                    'quantity_after' => $inventory->quantity_on_hand,
                ]);
            }
        }
        
        $order->update(['fulfillment_status' => 'unfulfilled']);
        
        Log::info("Inventory released for cancelled order", ['order_id' => $order->id]);
    }
    
    /**
     * Cancel order
     *
     * @param Order $order
     * @param string|null $reason
     * @return Order
     * @throws \Exception
     */
    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception("Order cannot be cancelled in current status: {$order->status}");
        }
        
        DB::beginTransaction();
        
        try {
            // Release inventory if fulfilled
            $this->releaseInventory($order);
            
            // Update order status
            $order->markAsCancelled();
            
            if ($reason) {
                $order->update(['admin_note' => $reason]);
            }
            
            DB::commit();
            
            Log::info("Order cancelled", ['order_id' => $order->id, 'reason' => $reason]);
            
            return $order->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to cancel order", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Get order statistics
     *
     * @return array
     */
    public function getOrderStatistics(): array
    {
        return [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::whereIn('status', ['confirmed', 'processing'])->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total'),
            'pending_payments' => Order::where('payment_status', 'pending')->sum('total'),
        ];
    }
}
