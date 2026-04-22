import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';

export interface Payment {
  id: number;
  order_id: number;
  amount: string | number;
  currency: string;
  status: string;
  payment_method: string;
  transaction_id: string | null;
  created_at: string;
}

export interface PaymentsResponse {
  data: Payment[];
  meta: { current_page: number; per_page: number; total: number; last_page: number };
}

export interface PaymentFilters {
  page?: number;
  per_page?: number;
  status?: string;
}

export interface RefundData {
  amount: number;
  reason: string;
}

export const paymentsApi = createApi({
  reducerPath: 'paymentsApi',
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
  tagTypes: ['Payment'],
  endpoints: (builder) => ({
    getPayments: builder.query<PaymentsResponse, PaymentFilters | void>({
      query: (filters = {}) => {
        const params = new URLSearchParams();
        if (filters?.page) params.append('page', filters.page.toString());
        if (filters?.per_page) params.append('per_page', filters.per_page.toString());
        if (filters?.status) params.append('status', filters.status);

        return `/payments?${params.toString()}`;
      },
      providesTags: (result) =>
        result
          ? [
              ...result.data.map(({ id }) => ({ type: 'Payment' as const, id })),
              { type: 'Payment', id: 'LIST' },
            ]
          : [{ type: 'Payment', id: 'LIST' }],
    }),

    processRefund: builder.mutation<{ message: string }, { orderId: number; data: RefundData }>({
      query: ({ orderId, data }) => ({
        url: `/orders/${orderId}/refund`,
        method: 'POST',
        body: data,
      }),
      invalidatesTags: [{ type: 'Payment', id: 'LIST' }],
    }),
  }),
});

export const {
  useGetPaymentsQuery,
  useProcessRefundMutation,
} = paymentsApi;
