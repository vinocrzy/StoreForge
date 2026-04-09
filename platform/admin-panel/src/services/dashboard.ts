import { apiClient } from './apiClient';
import type { BaseQueryFn } from '@reduxjs/toolkit/query';
import type { AxiosRequestConfig, AxiosError } from 'axios';

// Types
export interface DashboardStatistics {
  revenue: {
    total: number;
    previous_period: number;
    change_percentage: number;
    trend: 'up' | 'down';
  };
  orders: {
    total: number;
    pending: number;
    processing: number;
    completed: number;
    cancelled: number;
    previous_period: number;
    change_percentage: number;
    trend: 'up' | 'down';
  };
  customers: {
    total: number;
    new_this_period: number;
    previous_period: number;
    change_percentage: number;
    trend: 'up' | 'down';
  };
  products: {
    total: number;
    active: number;
    draft: number;
    low_stock: number;
    out_of_stock: number;
  };
  alerts: {
    low_stock_products: number;
    pending_orders: number;
    processing_orders: number;
    total_alerts: number;
  };
  period: string;
  date_range: {
    start: string;
    end: string;
  };
}

export interface RecentOrder {
  id: number;
  order_number: string;
  customer_id: number;
  customer: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  };
  status: string;
  payment_status: string;
  total_amount: string;
  items_count: number;
  created_at: string;
}

export interface SalesChartData {
  labels: string[];
  data: {
    revenue: number[];
    orders: number[];
    items: number[];
  };
  period: string;
  group_by: string;
}

export interface TopProduct {
  id: number;
  name: string;
  sku: string;
  price: string;
  total_quantity: number;
  total_revenue: string;
}

export interface TopProductsData {
  by_quantity: TopProduct[];
  by_revenue: TopProduct[];
  period: string;
}

export interface Activity {
  type: 'order' | 'customer' | 'product';
  action: string;
  description: string;
  amount?: string;
  email?: string;
  price?: string;
  status?: string;
  timestamp: string;
}

// Axios base query
const axiosBaseQuery = (
  { baseUrl }: { baseUrl: string } = { baseUrl: '' }
): BaseQueryFn<
  {
    url: string;
    method?: AxiosRequestConfig['method'];
    data?: AxiosRequestConfig['data'];
    params?: AxiosRequestConfig['params'];
  },
  unknown,
  unknown
> =>
  async ({ url, method = 'GET', data, params }) => {
    try {
      const result = await apiClient({
        url: baseUrl + url,
        method,
        data,
        params,
      });
      return { data: result.data };
    } catch (axiosError) {
      const err = axiosError as AxiosError;
      return {
        error: {
          status: err.response?.status,
          data: err.response?.data || err.message,
        },
      };
    }
  };

// Create API slice
import { createApi } from '@reduxjs/toolkit/query/react';

export const dashboardApi = createApi({
  reducerPath: 'dashboardApi',
  baseQuery: axiosBaseQuery({
    baseUrl: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  }),
  tagTypes: ['Dashboard'],
  endpoints: (builder) => ({
    // Get dashboard statistics
    getDashboardStatistics: builder.query<{ data: DashboardStatistics }, string>({
      query: (period = 'month') => ({
        url: '/dashboard/statistics',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Dashboard'],
    }),

    // Get recent orders
    getRecentOrders: builder.query<{ data: RecentOrder[] }, number>({
      query: (limit = 10) => ({
        url: '/dashboard/recent-orders',
        method: 'GET',
        params: { limit },
      }),
      providesTags: ['Dashboard'],
    }),

    // Get sales chart data
    getSalesChart: builder.query<
      { data: SalesChartData },
      { period?: string; group_by?: string }
    >({
      query: ({ period = 'month', group_by = 'day' }) => ({
        url: '/dashboard/sales-chart',
        method: 'GET',
        params: { period, group_by },
      }),
      providesTags: ['Dashboard'],
    }),

    // Get top products
    getTopProducts: builder.query<
      { data: TopProductsData },
      { limit?: number; period?: string }
    >({
      query: ({ limit = 10, period = 'month' }) => ({
        url: '/dashboard/top-products',
        method: 'GET',
        params: { limit, period },
      }),
      providesTags: ['Dashboard'],
    }),

    // Get activity log
    getActivityLog: builder.query<{ data: Activity[] }, number>({
      query: (limit = 20) => ({
        url: '/dashboard/activity-log',
        method: 'GET',
        params: { limit },
      }),
      providesTags: ['Dashboard'],
    }),
  }),
});

export const {
  useGetDashboardStatisticsQuery,
  useGetRecentOrdersQuery,
  useGetSalesChartQuery,
  useGetTopProductsQuery,
  useGetActivityLogQuery,
} = dashboardApi;
