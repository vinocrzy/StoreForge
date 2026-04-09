/**
 * Orders API Service
 * RTK Query endpoints for order management
 */

import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';
import type {
  Order,
  OrdersResponse,
  OrderResponse,
  OrderFilters,
  CreateOrderData,
  UpdateOrderData,
  UpdateOrderStatusData,
  CancelOrderData,
  RecordPaymentData,
  OrderStatistics,
  OrderStatisticsResponse,
} from '../types/order';

export const ordersApi = createApi({
  reducerPath: 'ordersApi',
  baseQuery: fetchBaseQuery({
    baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
    prepareHeaders: (headers, { getState }) => {
      const state = getState() as RootState;
      const token = state.auth.token || localStorage.getItem('auth_token');
      const storeId = state.auth.currentStore?.id || localStorage.getItem('store_id');

      if (token) {
        headers.set('Authorization', `Bearer ${token}`);
      }
      if (storeId) {
        headers.set('X-Store-ID', storeId.toString());
      }
      headers.set('Accept', 'application/json');
      headers.set('Content-Type', 'application/json');

      return headers;
    },
  }),
  tagTypes: ['Order', 'OrderStatistics'],
  endpoints: (builder) => ({
    // Get orders list with filters
    getOrders: builder.query<OrdersResponse, OrderFilters | void>({
      query: (filters = {}) => ({
        url: '/orders',
        params: filters || {},
      }),
      providesTags: (result) =>
        result?.data
          ? [
              ...result.data.map(({ id }) => ({ type: 'Order' as const, id })),
              { type: 'Order', id: 'LIST' },
            ]
          : [{ type: 'Order', id: 'LIST' }],
    }),

    // Get single order by ID
    getOrder: builder.query<Order, number>({
      query: (id) => `/orders/${id}`,
      transformResponse: (response: OrderResponse) => response.data,
      providesTags: (_result, _error, id) => [{ type: 'Order', id }],
    }),

    // Create new order
    createOrder: builder.mutation<Order, CreateOrderData>({
      query: (data) => ({
        url: '/orders',
        method: 'POST',
        body: data,
      }),
      transformResponse: (response: OrderResponse) => response.data,
      invalidatesTags: [
        { type: 'Order', id: 'LIST' },
        { type: 'OrderStatistics', id: 'CURRENT' },
      ],
    }),

    // Update order
    updateOrder: builder.mutation<Order, { id: number; data: UpdateOrderData }>({
      query: ({ id, data }) => ({
        url: `/orders/${id}`,
        method: 'PUT',
        body: data,
      }),
      transformResponse: (response: OrderResponse) => response.data,
      invalidatesTags: (_result, _error, { id }) => [{ type: 'Order', id }],
    }),

    // Delete order (soft delete)
    deleteOrder: builder.mutation<void, number>({
      query: (id) => ({
        url: `/orders/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: (_result, _error, id) => [
        { type: 'Order', id },
        { type: 'Order', id: 'LIST' },
        { type: 'OrderStatistics', id: 'CURRENT' },
      ],
    }),

    // Update order status
    updateOrderStatus: builder.mutation<Order, { id: number; data: UpdateOrderStatusData }>({
      query: ({ id, data }) => ({
        url: `/orders/${id}/status`,
        method: 'POST',
        body: data,
      }),
      transformResponse: (response: OrderResponse) => response.data,
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Order', id },
        { type: 'Order', id: 'LIST' },
        { type: 'OrderStatistics', id: 'CURRENT' },
      ],
    }),

    // Cancel order
    cancelOrder: builder.mutation<Order, { id: number; data: CancelOrderData }>({
      query: ({ id, data }) => ({
        url: `/orders/${id}/cancel`,
        method: 'POST',
        body: data,
      }),
      transformResponse: (response: OrderResponse) => response.data,
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Order', id },
        { type: 'Order', id: 'LIST' },
        { type: 'OrderStatistics', id: 'CURRENT' },
      ],
    }),

    // Record payment
    recordPayment: builder.mutation<OrderResponse, { id: number; data: RecordPaymentData }>({
      query: ({ id, data }) => ({
        url: `/orders/${id}/payment`,
        method: 'POST',
        body: data,
      }),
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Order', id },
        { type: 'Order', id: 'LIST' },
        { type: 'OrderStatistics', id: 'CURRENT' },
      ],
    }),

    // Fulfill order
    fulfillOrder: builder.mutation<Order, number>({
      query: (id) => ({
        url: `/orders/${id}/fulfill`,
        method: 'POST',
      }),
      transformResponse: (response: OrderResponse) => response.data,
      invalidatesTags: (_result, _error, id) => [
        { type: 'Order', id },
        { type: 'Order', id: 'LIST' },
      ],
    }),

    // Get order statistics
    getOrderStatistics: builder.query<OrderStatistics, void>({
      query: () => '/orders/statistics',
      transformResponse: (response: OrderStatisticsResponse) => response.data,
      providesTags: [{ type: 'OrderStatistics', id: 'CURRENT' }],
    }),

    // Export orders to CSV
    exportOrdersCsv: builder.mutation<Blob, Partial<OrderFilters>>({
      query: (filters = {}) => ({
        url: '/orders/export',
        method: 'GET',
        params: filters,
        responseHandler: async (response) => response.blob(),
        cache: 'no-cache',
      }),
    }),
  }),
});

// Export hooks for usage in components
export const {
  useGetOrdersQuery,
  useGetOrderQuery,
  useCreateOrderMutation,
  useUpdateOrderMutation,
  useDeleteOrderMutation,
  useUpdateOrderStatusMutation,
  useCancelOrderMutation,
  useRecordPaymentMutation,
  useFulfillOrderMutation,
  useGetOrderStatisticsQuery,
  useExportOrdersCsvMutation,
} = ordersApi;
