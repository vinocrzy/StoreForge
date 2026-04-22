import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';

// Types
export interface Coupon {
  id: number;
  code: string;
  type: 'percentage' | 'fixed';
  value: number;
  status: 'active' | 'inactive' | 'expired';
  usage_limit: number | null;
  used_count: number;
  usage_limit_per_customer: number | null;
  minimum_purchase_amount: number | null;
  maximum_discount_amount: number | null;
  starts_at: string | null;
  expires_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface CouponsResponse {
  data: Coupon[];
  meta: { current_page: number; per_page: number; total: number; last_page: number };
}

export interface CouponFilters {
  page?: number;
  per_page?: number;
  status?: string;
  search?: string;
}

export interface CreateCouponData {
  code: string;
  type: 'percentage' | 'fixed';
  value: number;
  status?: string;
  usage_limit?: number | null;
  usage_limit_per_customer?: number | null;
  minimum_purchase_amount?: number | null;
  maximum_discount_amount?: number | null;
  starts_at?: string | null;
  expires_at?: string | null;
}

export interface UpdateCouponData extends Partial<CreateCouponData> {
  id: number;
}

export const couponsApi = createApi({
  reducerPath: 'couponsApi',
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
  tagTypes: ['Coupon'],
  endpoints: (builder) => ({
    getCoupons: builder.query<CouponsResponse, CouponFilters | void>({
      query: (filters = {}) => {
        const params = new URLSearchParams();
        if (filters?.page) params.append('page', filters.page.toString());
        if (filters?.per_page) params.append('per_page', filters.per_page.toString());
        if (filters?.status) params.append('status', filters.status);
        if (filters?.search) params.append('search', filters.search);

        return `/coupons?${params.toString()}`;
      },
      providesTags: (result) =>
        result
          ? [
              ...result.data.map(({ id }) => ({ type: 'Coupon' as const, id })),
              { type: 'Coupon', id: 'LIST' },
            ]
          : [{ type: 'Coupon', id: 'LIST' }],
    }),

    getCoupon: builder.query<{ data: Coupon }, number>({
      query: (id) => `/coupons/${id}`,
      providesTags: (_result, _error, id) => [{ type: 'Coupon', id }],
    }),

    createCoupon: builder.mutation<{ data: Coupon }, CreateCouponData>({
      query: (body) => ({
        url: '/coupons',
        method: 'POST',
        body,
      }),
      invalidatesTags: [{ type: 'Coupon', id: 'LIST' }],
    }),

    updateCoupon: builder.mutation<{ data: Coupon }, UpdateCouponData>({
      query: ({ id, ...body }) => ({
        url: `/coupons/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Coupon', id },
        { type: 'Coupon', id: 'LIST' },
      ],
    }),

    deleteCoupon: builder.mutation<{ message: string }, number>({
      query: (id) => ({
        url: `/coupons/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: [{ type: 'Coupon', id: 'LIST' }],
    }),
  }),
});

export const {
  useGetCouponsQuery,
  useGetCouponQuery,
  useCreateCouponMutation,
  useUpdateCouponMutation,
  useDeleteCouponMutation,
} = couponsApi;
