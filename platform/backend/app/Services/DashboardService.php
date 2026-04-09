<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get comprehensive dashboard statistics
     *
     * @param int $storeId
     * @param string $period ('today', 'week', 'month', 'year')
     * @return array
     */
    public function getStatistics(int $storeId, string $period = 'month'): array
    {
        $dateRange = $this->getDateRange($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        return [
            'revenue' => $this->getRevenue($storeId, $startDate, $endDate),
            'orders' => $this->getOrdersStatistics($storeId, $startDate, $endDate),
            'customers' => $this->getCustomersStatistics($storeId, $startDate, $endDate),
            'products' => $this->getProductsStatistics($storeId),
            'alerts' => $this->getAlerts($storeId),
            'period' => $period,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get recent orders
     *
     * @param int $storeId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentOrders(int $storeId, int $limit = 10)
    {
        return Order::where('store_id', $storeId)
            ->with(['customer:id,first_name,last_name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sales chart data
     *
     * @param int $storeId
     * @param string $period ('week', 'month', 'year')
     * @param string $groupBy ('day', 'week', 'month')
     * @return array
     */
    public function getSalesChart(int $storeId, string $period = 'month', string $groupBy = 'day'): array
    {
        $dateRange = $this->getDateRange($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $salesData = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as date"),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('SUM(items_count) as items_sold')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $salesData->pluck('date')->toArray(),
            'data' => [
                'revenue' => $salesData->pluck('revenue')->map(fn($v) => (float) $v)->toArray(),
                'orders' => $salesData->pluck('orders_count')->toArray(),
                'items' => $salesData->pluck('items_sold')->toArray(),
            ],
            'period' => $period,
            'group_by' => $groupBy,
        ];
    }

    /**
     * Get top-selling products
     *
     * @param int $storeId
     * @param int $limit
     * @param string $period
     * @return array
     */
    public function getTopProducts(int $storeId, int $limit = 10, string $period = 'month'): array
    {
        $dateRange = $this->getDateRange($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $topByQuantity = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.store_id', $storeId)
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.created_at', '<=', $endDate)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.price',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.price')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get();

        $topByRevenue = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.store_id', $storeId)
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.created_at', '<=', $endDate)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.price',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.price')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();

        return [
            'by_quantity' => $topByQuantity,
            'by_revenue' => $topByRevenue,
            'period' => $period,
        ];
    }

    /**
     * Get activity log (recent changes)
     *
     * @param int $storeId
     * @param int $limit
     * @return array
     */
    public function getActivityLog(int $storeId, int $limit = 20): array
    {
        $recentOrders = Order::where('store_id', $storeId)
            ->with('customer:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'type' => 'order',
                    'action' => 'created',
                    'description' => "New order #{$order->order_number} from {$order->customer->first_name} {$order->customer->last_name}",
                    'amount' => $order->total_amount,
                    'status' => $order->status,
                    'timestamp' => $order->created_at,
                ];
            });

        $recentCustomers = Customer::where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'type' => 'customer',
                    'action' => 'registered',
                    'description' => "New customer: {$customer->first_name} {$customer->last_name}",
                    'email' => $customer->email,
                    'timestamp' => $customer->created_at,
                ];
            });

        $recentProducts = Product::where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'type' => 'product',
                    'action' => 'added',
                    'description' => "New product: {$product->name}",
                    'price' => $product->price,
                    'timestamp' => $product->created_at,
                ];
            });

        $activities = $recentOrders
            ->concat($recentCustomers)
            ->concat($recentProducts)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return $activities->toArray();
    }

    /**
     * Get revenue statistics
     */
    private function getRevenue(int $storeId, Carbon $startDate, Carbon $endDate): array
    {
        $totalRevenue = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->whereIn('status', ['completed', 'processing'])
            ->sum('total_amount');

        $previousPeriod = $this->getPreviousPeriod($startDate, $endDate);
        $previousRevenue = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $previousPeriod['start'])
            ->where('created_at', '<=', $previousPeriod['end'])
            ->whereIn('status', ['completed', 'processing'])
            ->sum('total_amount');

        $change = $previousRevenue > 0 
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        return [
            'total' => (float) $totalRevenue,
            'previous_period' => (float) $previousRevenue,
            'change_percentage' => round($change, 2),
            'trend' => $change >= 0 ? 'up' : 'down',
        ];
    }

    /**
     * Get orders statistics
     */
    private function getOrdersStatistics(int $storeId, Carbon $startDate, Carbon $endDate): array
    {
        $totalOrders = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->count();

        $ordersByStatus = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $pendingOrders = $ordersByStatus['pending'] ?? 0;
        $processingOrders = $ordersByStatus['processing'] ?? 0;
        $completedOrders = $ordersByStatus['completed'] ?? 0;
        $cancelledOrders = $ordersByStatus['cancelled'] ?? 0;

        $previousPeriod = $this->getPreviousPeriod($startDate, $endDate);
        $previousOrders = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $previousPeriod['start'])
            ->where('created_at', '<=', $previousPeriod['end'])
            ->count();

        $change = $previousOrders > 0 
            ? (($totalOrders - $previousOrders) / $previousOrders) * 100 
            : 0;

        return [
            'total' => $totalOrders,
            'pending' => $pendingOrders,
            'processing' => $processingOrders,
            'completed' => $completedOrders,
            'cancelled' => $cancelledOrders,
            'previous_period' => $previousOrders,
            'change_percentage' => round($change, 2),
            'trend' => $change >= 0 ? 'up' : 'down',
        ];
    }

    /**
     * Get customers statistics
     */
    private function getCustomersStatistics(int $storeId, Carbon $startDate, Carbon $endDate): array
    {
        $newCustomers = Customer::where('store_id', $storeId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->count();

        $totalCustomers = Customer::where('store_id', $storeId)->count();

        $previousPeriod = $this->getPreviousPeriod($startDate, $endDate);
        $previousNewCustomers = Customer::where('store_id', $storeId)
            ->where('created_at', '>=', $previousPeriod['start'])
            ->where('created_at', '<=', $previousPeriod['end'])
            ->count();

        $change = $previousNewCustomers > 0 
            ? (($newCustomers - $previousNewCustomers) / $previousNewCustomers) * 100 
            : 0;

        return [
            'total' => $totalCustomers,
            'new_this_period' => $newCustomers,
            'previous_period' => $previousNewCustomers,
            'change_percentage' => round($change, 2),
            'trend' => $change >= 0 ? 'up' : 'down',
        ];
    }

    /**
     * Get products statistics
     */
    private function getProductsStatistics(int $storeId): array
    {
        $totalProducts = Product::where('store_id', $storeId)->count();
        $activeProducts = Product::where('store_id', $storeId)
            ->where('status', 'active')
            ->count();
        $draftProducts = Product::where('store_id', $storeId)
            ->where('status', 'draft')
            ->count();
        $lowStockProducts = Product::where('store_id', $storeId)
            ->where('track_inventory', true)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', DB::raw('low_stock_threshold'))
            ->count();
        $outOfStockProducts = Product::where('store_id', $storeId)
            ->where('track_inventory', true)
            ->where('stock_quantity', 0)
            ->count();

        return [
            'total' => $totalProducts,
            'active' => $activeProducts,
            'draft' => $draftProducts,
            'low_stock' => $lowStockProducts,
            'out_of_stock' => $outOfStockProducts,
        ];
    }

    /**
     * Get alerts (low stock, pending orders, etc.)
     */
    private function getAlerts(int $storeId): array
    {
        $lowStockCount = Product::where('store_id', $storeId)
            ->where('track_inventory', true)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', DB::raw('low_stock_threshold'))
            ->count();

        $pendingOrdersCount = Order::where('store_id', $storeId)
            ->where('status', 'pending')
            ->count();

        $processingOrdersCount = Order::where('store_id', $storeId)
            ->where('status', 'processing')
            ->count();

        return [
            'low_stock_products' => $lowStockCount,
            'pending_orders' => $pendingOrdersCount,
            'processing_orders' => $processingOrdersCount,
            'total_alerts' => $lowStockCount + $pendingOrdersCount,
        ];
    }

    /**
     * Get date range based on period
     */
    private function getDateRange(string $period): array
    {
        $endDate = Carbon::now()->endOfDay();

        $startDate = match($period) {
            'today' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Get previous period date range
     */
    private function getPreviousPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $duration = $startDate->diffInDays($endDate);

        return [
            'start' => $startDate->copy()->subDays($duration + 1),
            'end' => $startDate->copy()->subDay(),
        ];
    }
}
