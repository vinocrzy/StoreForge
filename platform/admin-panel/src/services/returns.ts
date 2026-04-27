import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';

export interface ReturnItem {
  id: number;
  order_item_id: number;
  quantity: number;
  reason: string | null;
  product_name?: string;
}

export interface ReturnRequest {
  id: number;
  store_id: number;
  order_id: number;
  customer_id: number;
  return_number: string;
  reason: 'damaged' | 'wrong_item' | 'not_as_described' | 'changed_mind' | 'other';
  reason_details: string | null;
  status: 'requested' | 'approved' | 'rejected' | 'received' | 'refunded';
  refund_amount: number | null;
  admin_notes: string | null;
  created_at: string;
  updated_at: string;
  order?: { order_number: string; total_amount: number };
  customer?: { first_name: string; last_name: string; email: string };
  items?: ReturnItem[];
}

export interface ReturnsListResponse {
  data: ReturnRequest[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export const returnsApi = createApi({
  reducerPath: 'returnsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
    prepareHeaders: (headers, { getState }) => {
      const state = getState() as RootState;
      const token = state.auth.token || localStorage.getItem('auth_token');
      const storeId = state.auth.currentStore?.id || localStorage.getItem('store_id');
      if (token) headers.set('Authorization', `Bearer ${token}`);
      if (storeId) headers.set('X-Store-ID', storeId.toString());
      headers.set('Content-Type', 'application/json');
      headers.set('Accept', 'application/json');
      return headers;
    },
  }),
  tagTypes: ['Returns'],
  endpoints: (builder) => ({
    getReturns: builder.query<ReturnsListResponse, { page?: number; status?: string }>({
      query: ({ page = 1, status } = {}) => {
        const params = new URLSearchParams({ page: page.toString() });
        if (status) params.set('status', status);
        return `/admin/returns?${params}`;
      },
      providesTags: ['Returns'],
    }),
    getReturn: builder.query<{ data: ReturnRequest }, number>({
      query: (id) => `/admin/returns/${id}`,
      providesTags: (_r, _e, id) => [{ type: 'Returns', id }],
    }),
    approveReturn: builder.mutation<{ data: ReturnRequest }, { id: number; refund_amount: number; admin_notes?: string }>({
      query: ({ id, ...body }) => ({ url: `/admin/returns/${id}/approve`, method: 'PATCH', body }),
      invalidatesTags: ['Returns'],
    }),
    rejectReturn: builder.mutation<{ data: ReturnRequest }, { id: number; admin_notes: string }>({
      query: ({ id, ...body }) => ({ url: `/admin/returns/${id}/reject`, method: 'PATCH', body }),
      invalidatesTags: ['Returns'],
    }),
    processRefund: builder.mutation<{ data: ReturnRequest; message: string }, number>({
      query: (id) => ({ url: `/admin/returns/${id}/refund`, method: 'POST' }),
      invalidatesTags: ['Returns'],
    }),
  }),
});

export const {
  useGetReturnsQuery,
  useGetReturnQuery,
  useApproveReturnMutation,
  useRejectReturnMutation,
  useProcessRefundMutation,
} = returnsApi;
