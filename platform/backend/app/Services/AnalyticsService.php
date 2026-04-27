<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Parse a period string ('7d', '30d', '90d') and return [startDate, endDate].
     *
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function parsePeriod(string $period): array
    {
        $days = match ($period) {
            '7d'  => 7,
            '90d' => 90,
            default => 30,  // '30d' and anything else
        };

        return [now()->subDays($days)->startOfDay(), now()->endOfDay()];
    }

    /**
     * Get dashboard KPIs: revenue, orders, AOV, new customers — today vs yesterday.
     */
    public function getDashboardKPIs(int $storeId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        $todayStart = now()->startOfDay();
        $todayEnd   = now()->endOfDay();
        $yesterdayStart = now()->subDay()->startOfDay();
        $yesterdayEnd   = now()->subDay()->endOfDay();

        $period_stats = DB::table('orders')
            ->where('store_id', $storeId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('SUM(total) as revenue, COUNT(*) as order_count, AVG(total) as aov')
            ->first();

        $today_stats = DB::table('orders')
            ->where('store_id', $storeId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->selectRaw('SUM(total) as revenue, COUNT(*) as order_count, AVG(total) as aov')
            ->first();

        $yesterday_stats = DB::table('orders')
            ->where('store_id', $storeId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
            ->selectRaw('SUM(total) as revenue, COUNT(*) as order_count, AVG(total) as aov')
            ->first();

        $new_customers_today = DB::table('customers')
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        $new_customers_yesterday = DB::table('customers')
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
            ->count();

        return [
            'period_revenue'    => round((float) ($period_stats->revenue ?? 0), 2),
            'period_orders'     => (int) ($period_stats->order_count ?? 0),
            'period_aov'        => round((float) ($period_stats->aov ?? 0), 2),
            'today_revenue'     => round((float) ($today_stats->revenue ?? 0), 2),
            'today_orders'      => (int) ($today_stats->order_count ?? 0),
            'today_aov'         => round((float) ($today_stats->aov ?? 0), 2),
            'yesterday_revenue' => round((float) ($yesterday_stats->revenue ?? 0), 2),
            'yesterday_orders'  => (int) ($yesterday_stats->order_count ?? 0),
            'yesterday_aov'     => round((float) ($yesterday_stats->aov ?? 0), 2),
            'new_customers_today'     => $new_customers_today,
            'new_customers_yesterday' => $new_customers_yesterday,
        ];
    }

    /**
     * Get daily revenue data for the given period.
     *
     * @return array  Array of { date, revenue, orders }
     */
    public function getRevenueChart(int $storeId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        $rows = DB::table('orders')
            ->where('store_id', $storeId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return $rows->map(fn($row) => [
            'date'    => $row->date,
            'revenue' => round((float) $row->revenue, 2),
            'orders'  => (int) $row->orders,
        ])->values()->toArray();
    }

    /**
     * Get top products by revenue.
     *
     * @return array
     */
    public function getTopProducts(int $storeId, string $period = '30d', int $limit = 10): array
    {
        [$start, $end] = $this->parsePeriod($period);

        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.store_id', $storeId)
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$start, $end])
            ->selectRaw('
                order_items.product_id,
                products.name,
                products.sku,
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.total) as revenue
            ')
            ->groupBy('order_items.product_id', 'products.name', 'products.sku')
            ->orderByRaw('SUM(order_items.total) DESC')
            ->limit($limit)
            ->get();

        return $rows->map(fn($row) => [
            'product_id' => $row->product_id,
            'name'       => $row->name,
            'sku'        => $row->sku,
            'units_sold' => (int) $row->units_sold,
            'revenue'    => round((float) $row->revenue, 2),
        ])->values()->toArray();
    }

    /**
     * Get order count grouped by status.
     */
    public function getOrderStatusBreakdown(int $storeId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        $rows = DB::table('orders')
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return $rows->mapWithKeys(fn($row) => [$row->status => (int) $row->count])->toArray();
    }

    /**
     * Get recent orders with basic info.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRecentOrders(int $storeId, int $limit = 10)
    {
        return DB::table('orders')
            ->where('store_id', $storeId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->select(['id', 'order_number', 'status', 'payment_status', 'total', 'created_at', 'customer_id'])
            ->get();
    }

    /**
     * Get new customer registrations per day.
     */
    public function getCustomerRegistrations(int $storeId, string $period = '30d'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        $rows = DB::table('customers')
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return $rows->map(fn($row) => [
            'date'  => $row->date,
            'count' => (int) $row->count,
        ])->values()->toArray();
    }

    /**
     * Assemble the full dashboard response, cached per store+period for 5 minutes.
     */
    public function getDashboard(int $storeId, string $period = '30d'): array
    {
        $cacheKey = "analytics:dashboard:{$storeId}:{$period}";

        return Cache::remember($cacheKey, 300, function () use ($storeId, $period) {
            return [
                'kpis'             => $this->getDashboardKPIs($storeId, $period),
                'revenue_chart'    => $this->getRevenueChart($storeId, $period),
                'top_products'     => $this->getTopProducts($storeId, $period),
                'status_breakdown' => $this->getOrderStatusBreakdown($storeId, $period),
                'recent_orders'    => $this->getRecentOrders($storeId, 10),
            ];
        });
    }

    /**
     * Invalidate the dashboard cache for a store (call on new order).
     */
    public function invalidateCache(int $storeId): void
    {
        foreach (['7d', '30d', '90d'] as $p) {
            Cache::forget("analytics:dashboard:{$storeId}:{$p}");
        }
    }
}
