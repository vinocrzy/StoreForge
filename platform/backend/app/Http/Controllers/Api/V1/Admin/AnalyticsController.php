<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Analytics (Admin)
 *
 * Revenue, order, customer, and product analytics for the store.
 *
 * @authenticated
 */
class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    /**
     * Dashboard overview
     *
     * Returns KPIs, revenue chart, top products, order status breakdown, and recent orders
     * for the given period. Response is cached for 5 minutes per store.
     *
     * @queryParam period string Period: 7d, 30d, 90d. Defaults to 30d. Example: 30d
     *
     * @response 200 {
     *   "data": {
     *     "kpis": {"period_revenue": 4200.00, "period_orders": 35, "today_revenue": 150.00},
     *     "revenue_chart": [{"date": "2026-04-01", "revenue": 120.00, "orders": 3}],
     *     "top_products": [{"product_id": 1, "name": "Honey Soap", "revenue": 800.00}],
     *     "status_breakdown": {"pending": 5, "delivered": 28, "cancelled": 2},
     *     "recent_orders": []
     *   }
     * }
     */
    public function dashboard(Request $request): JsonResponse
    {
        $period = $this->validatePeriod($request->input('period', '30d'));

        $data = $this->analytics->getDashboard(tenant()->id, $period);

        return response()->json(['data' => $data]);
    }

    /**
     * Revenue chart
     *
     * Daily revenue and order count for the given period.
     *
     * @queryParam period string Period: 7d, 30d, 90d. Example: 30d
     *
     * @response 200 {"data": [{"date": "2026-04-01", "revenue": 120.00, "orders": 3}]}
     */
    public function revenue(Request $request): JsonResponse
    {
        $period = $this->validatePeriod($request->input('period', '30d'));

        return response()->json([
            'data' => $this->analytics->getRevenueChart(tenant()->id, $period),
        ]);
    }

    /**
     * Top products
     *
     * Top-selling products by revenue for the given period.
     *
     * @queryParam period string Period: 7d, 30d, 90d. Example: 30d
     * @queryParam limit int Number of products to return (max 50). Example: 10
     *
     * @response 200 {"data": [{"product_id": 1, "name": "Honey Soap", "units_sold": 42, "revenue": 800.00}]}
     */
    public function topProducts(Request $request): JsonResponse
    {
        $period = $this->validatePeriod($request->input('period', '30d'));
        $limit  = min((int) $request->input('limit', 10), 50);

        return response()->json([
            'data' => $this->analytics->getTopProducts(tenant()->id, $period, $limit),
        ]);
    }

    /**
     * Customer registrations
     *
     * New customer registrations per day for the given period.
     *
     * @queryParam period string Period: 7d, 30d, 90d. Example: 30d
     *
     * @response 200 {"data": [{"date": "2026-04-01", "count": 4}]}
     */
    public function customers(Request $request): JsonResponse
    {
        $period = $this->validatePeriod($request->input('period', '30d'));

        return response()->json([
            'data' => $this->analytics->getCustomerRegistrations(tenant()->id, $period),
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function validatePeriod(string $period): string
    {
        return in_array($period, ['7d', '30d', '90d']) ? $period : '30d';
    }
}
