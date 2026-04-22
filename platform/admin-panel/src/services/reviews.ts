import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';

// Types
export interface Review {
  id: number;
  rating: number;
  title: string | null;
  body: string;
  status: 'pending' | 'approved' | 'rejected';
  is_verified_purchase: boolean;
  admin_response: string | null;
  admin_responded_at: string | null;
  rejection_reason: string | null;
  customer: { id: number; first_name: string; last_name: string; email?: string };
  product: { id: number; name: string; slug: string };
  order?: { id: number; order_number: string; status: string };
  created_at: string;
}

export interface ReviewsResponse {
  data: Review[];
  meta: { current_page: number; per_page: number; total: number; last_page: number };
}

export interface ReviewFilters {
  page?: number;
  per_page?: number;
  status?: string;
  product_id?: number;
}

export interface UpdateReviewData {
  id: number;
  status?: 'pending' | 'approved' | 'rejected';
  admin_response?: string | null;
  rejection_reason?: string | null;
}

export const reviewsApi = createApi({
  reducerPath: 'reviewsApi',
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
  tagTypes: ['Review'],
  endpoints: (builder) => ({
    getReviews: builder.query<ReviewsResponse, ReviewFilters | void>({
      query: (filters = {}) => {
        const params = new URLSearchParams();
        if (filters?.page) params.append('page', filters.page.toString());
        if (filters?.per_page) params.append('per_page', filters.per_page.toString());
        if (filters?.status) params.append('status', filters.status);
        if (filters?.product_id) params.append('product_id', filters.product_id.toString());

        return `/reviews?${params.toString()}`;
      },
      providesTags: (result) =>
        result
          ? [
              ...result.data.map(({ id }) => ({ type: 'Review' as const, id })),
              { type: 'Review', id: 'LIST' },
            ]
          : [{ type: 'Review', id: 'LIST' }],
    }),

    getReview: builder.query<{ data: Review }, number>({
      query: (id) => `/reviews/${id}`,
      providesTags: (_result, _error, id) => [{ type: 'Review', id }],
    }),

    updateReview: builder.mutation<{ data: Review }, UpdateReviewData>({
      query: ({ id, ...body }) => ({
        url: `/reviews/${id}`,
        method: 'PATCH',
        body,
      }),
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Review', id },
        { type: 'Review', id: 'LIST' },
      ],
    }),

    deleteReview: builder.mutation<{ message: string }, number>({
      query: (id) => ({
        url: `/reviews/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: [{ type: 'Review', id: 'LIST' }],
    }),
  }),
});

export const {
  useGetReviewsQuery,
  useGetReviewQuery,
  useUpdateReviewMutation,
  useDeleteReviewMutation,
} = reviewsApi;
