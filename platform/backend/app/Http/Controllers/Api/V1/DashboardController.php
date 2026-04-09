<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Dashboard
 *
 * APIs for dashboard statistics and analytics
 */
class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Get dashboard statistics
     *
     * Returns comprehensive dashboard statistics including revenue, orders, customers, products, and alerts.
     *
     * @queryParam period string The time period for statistics. Options: today, week, month, year. Defaults to month. Example: month
     *
     * @response 200 {
     *   "data": {
     *     "revenue": {
     *       "total": 15500.50,
     *       "previous_period": 12300.00,
     *       "change_percentage": 26.02,
     *       "trend": "up"
     *     },
     *     "orders": {
     *       "total": 145,
     *       "pending": 12,
     *       "processing": 23,
     *       "completed": 98,
     *       "cancelled": 12,
     *       "previous_period": 120,
     *       "change_percentage": 20.83,
     *       "trend": "up"
     *     },
     *     "customers": {
     *       "total": 523,
     *       "new_this_period": 45,
     *       "previous_period": 38,
     *       "change_percentage": 18.42,
     *       "trend": "up"
     *     },
     *     "products": {
     *       "total": 120,
     *       "active": 105,
     *       "draft": 15,
     *       "low_stock": 8,
     *       "out_of_stock": 3
     *     },
     *     "alerts": {
     *       "low_stock_products": 8,
     *       "pending_orders": 12,
     *       "processing_orders": 23,
     *       "total_alerts": 20
     *     },
     *     "period": "month",
     *     "date_range": {
     *       "start": "2026-04-01",
     *       "end": "2026-04-08"
     *     }
     *   }
     * }
     */
    public function statistics(Request $request): JsonResponse
    {
        $period = $request->query('period', 'month');

        $statistics = $this->dashboardService->getStatistics(
            tenant()->id,
            $period
        );

        return response()->json([
            'data' => $statistics
        ]);
    }

    /**
     * Get recent orders
     *
     * Returns recent orders with customer information.
     *
     * @queryParam limit integer Number of orders to return. Defaults to 10. Example: 10
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "order_number": "ORD-2026-001",
     *       "customer_id": 5,
     *       "customer": {
     *         "id": 5,
     *         "first_name": "John",
     *         "last_name": "Doe",
     *         "email": "john@example.com"
     *       },
     *       "status": "processing",
     *       "payment_status": "paid",
     *       "total_amount": "125.50",
     *       "items_count": 3,
     *       "created_at": "2026-04-08T10:30:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function recentOrders(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);

        $orders = $this->dashboardService->getRecentOrders(
            tenant()->id,
            $limit
        );

        return response()->json([
            'data' => $orders
        ]);
    }

    /**
     * Get sales chart data
     *
     * Returns sales chart data grouped by day, week, or month.
     *
     * @queryParam period string Time period for the chart. Options: week, month, year. Defaults to month. Example: month
     * @queryParam group_by string How to group the data. Options: day, week, month. Defaults to day. Example: day
     *
     * @response 200 {
     *   "data": {
     *     "labels": ["2026-04-01", "2026-04-02", "2026-04-03", "2026-04-04", "2026-04-05"],
     *     "data": {
     *       "revenue": [1250.50, 980.00, 1550.75, 2100.00, 1820.25],
     *       "orders": [15, 12, 18, 25, 20],
     *       "items": [35, 28, 42, 58, 48]
     *     },
     *     "period": "week",
     *     "group_by": "day"
     *   }
     * }
     */
    public function salesChart(Request $request): JsonResponse
    {
        $period = $request->query('period', 'month');
        $groupBy = $request->query('group_by', 'day');

        $chartData = $this->dashboardService->getSalesChart(
            tenant()->id,
            $period,
            $groupBy
        );

        return response()->json([
            'data' => $chartData
        ]);
    }

    /**
     * Get top products
     *
     * Returns top-selling products by quantity and revenue.
     *
     * @queryParam limit integer Number of products to return. Defaults to 10. Example: 10
     * @queryParam period string Time period for analysis. Options: week, month, year. Defaults to month. Example: month
     *
     * @response 200 {
     *   "data": {
     *     "by_quantity": [
     *       {
     *         "id": 1,
     *         "name": "Lavender Honey Soap",
     *         "sku": "LHS-001",
     *         "price": "12.99",
     *         "total_quantity": 156,
     *         "total_revenue": "2026.44"
     *       }
     *     ],
     *     "by_revenue": [
     *       {
     *         "id": 2,
     *         "name": "Premium Honey Gift Set",
     *         "sku": "PHGS-001",
     *         "price": "49.99",
     *         "total_quantity": 45,
     *         "total_revenue": "2249.55"
     *       }
     *     ],
     *     "period": "month"
     *   }
     * }
     */
    public function topProducts(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $period = $request->query('period', 'month');

        $topProducts = $this->dashboardService->getTopProducts(
            tenant()->id,
            $limit,
            $period
        );

        return response()->json([
            'data' => $topProducts
        ]);
    }

    /**
     * Get activity log
     *
     * Returns recent activity log including orders, customers, and products.
     *
     * @queryParam limit integer Number of activities to return. Defaults to 20. Example: 20
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "type": "order",
     *       "action": "created",
     *       "description": "New order #ORD-2026-001 from John Doe",
     *       "amount": "125.50",
     *       "status": "pending",
     *       "timestamp": "2026-04-08T10:30:00.000000Z"
     *     },
     *     {
     *       "type": "customer",
     *       "action": "registered",
     *       "description": "New customer: Jane Smith",
     *       "email": "jane@example.com",
     *       "timestamp": "2026-04-08T09:15:00.000000Z"
     *     },
     *     {
     *       "type": "product",
     *       "action": "added",
     *       "description": "New product: Organic Honey",
     *       "price": "15.99",
     *       "timestamp": "2026-04-07T16:45:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function activityLog(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 20);

        $activities = $this->dashboardService->getActivityLog(
            tenant()->id,
            $limit
        );

        return response()->json([
            'data' => $activities
        ]);
    }
}
