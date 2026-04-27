import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';

export type AnalyticsPeriod = '7d' | '30d' | '90d';

export interface KPIMetric {
  value: number;
  previous: number;
  change_pct: number;
  trend: 'up' | 'down' | 'flat';
}

export interface DashboardKPIs {
  revenue: KPIMetric;
  orders: KPIMetric;
  aov: KPIMetric;
  new_customers: KPIMetric;
}

export interface RevenuePoint {
  date: string;
  revenue: number;
  orders: number;
}

export interface TopProduct {
  id: number;
  name: string;
  sku: string;
  units_sold: number;
  revenue: number;
  rank: number;
}

export interface OrderStatusBreakdown {
  status: string;
  count: number;
  percentage: number;
}

export interface DashboardData {
  kpis: DashboardKPIs;
  revenue_chart: RevenuePoint[];
  top_products: TopProduct[];
  order_status: OrderStatusBreakdown[];
  recent_orders: {
    id: number;
    order_number: string;
    customer_name: string;
    total: number;
    status: string;
    created_at: string;
  }[];
  period: AnalyticsPeriod;
}

export interface AbandonedCartStat {
  id: number;
  token: string;
  customer_name: string;
  customer_email: string;
  item_count: number;
  total_value: number;
  abandoned_at: string;
  recovery_email_count: number;
}

export interface AbandonedCartAnalytics {
  total_abandoned: number;
  total_value: number;
  recovered_count: number;
  recovery_rate: number;
  recovered_revenue: number;
  carts: AbandonedCartStat[];
}

export const analyticsApi = createApi({
  reducerPath: 'analyticsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
    prepareHeaders: (headers, { getState }) => {
      const state = getState() as RootState;
      const token = state.auth.token || localStorage.getItem('auth_token');
      const storeId = state.auth.currentStore?.id || localStorage.getItem('store_id');
      if (token) headers.set('Authorization', `Bearer ${token}`);
      if (storeId) headers.set('X-Store-ID', storeId.toString());
      headers.set('Accept', 'application/json');
      return headers;
    },
  }),
  tagTypes: ['Analytics', 'AbandonedCarts'],
  endpoints: (builder) => ({
    getDashboard: builder.query<{ data: DashboardData }, { period?: AnalyticsPeriod }>({
      query: ({ period = '30d' } = {}) => `/admin/analytics/dashboard?period=${period}`,
      providesTags: ['Analytics'],
    }),
    getAbandonedCarts: builder.query<{ data: AbandonedCartAnalytics }, void>({
      query: () => '/admin/abandoned-carts',
      providesTags: ['AbandonedCarts'],
    }),
  }),
});

export const { useGetDashboardQuery, useGetAbandonedCartsQuery } = analyticsApi;
