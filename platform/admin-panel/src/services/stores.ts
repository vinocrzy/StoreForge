/**
 * Stores API Service
 * RTK Query endpoints for store management (super admin)
 */

import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';
import type {
  StoresResponse,
  StoreCreateResponse,
  StoreDetailsResponse,
  CreateStoreData,
  UpdateStoreStatusData,
  StoreFilters,
} from '../types/store';

export const storesApi = createApi({
  reducerPath: 'storesApi',
  baseQuery: fetchBaseQuery({
    baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
    prepareHeaders: (headers, { getState }) => {
      const state = getState() as RootState;
      const token = state.auth.token || localStorage.getItem('auth_token');

      if (token) {
        headers.set('Authorization', `Bearer ${token}`);
      }
      headers.set('Content-Type', 'application/json');
      headers.set('Accept', 'application/json');

      return headers;
    },
  }),
  tagTypes: ['Store'],
  endpoints: (builder) => ({
    // Get list of stores
    getStores: builder.query<StoresResponse, StoreFilters | undefined>({
      query: (filters = {}) => {
        const params = new URLSearchParams();
        if (filters.search) params.append('search', filters.search);
        if (filters.status) params.append('status', filters.status);
        if (filters.page) params.append('page', filters.page.toString());
        if (filters.per_page) params.append('per_page', filters.per_page.toString());
        
        return `/stores?${params.toString()}`;
      },
      providesTags: (result) =>
        result
          ? [
              ...result.data.map(({ id }) => ({ type: 'Store' as const, id })),
              { type: 'Store', id: 'LIST' },
            ]
          : [{ type: 'Store', id: 'LIST' }],
    }),

    // Get single store
    getStore: builder.query<StoreDetailsResponse, number>({
      query: (id) => `/stores/${id}`,
      providesTags: (_result, _error, id) => [{ type: 'Store', id }],
    }),

    // Create store
    createStore: builder.mutation<StoreCreateResponse, CreateStoreData>({
      query: (data) => ({
        url: '/stores',
        method: 'POST',
        body: data,
      }),
      invalidatesTags: [{ type: 'Store', id: 'LIST' }],
    }),

    // Update store status
    updateStoreStatus: builder.mutation<StoreDetailsResponse['data'], { id: number; data: UpdateStoreStatusData }>({
      query: ({ id, data }) => ({
        url: `/stores/${id}/status`,
        method: 'PATCH',
        body: data,
      }),
      transformResponse: (response: { data: StoreDetailsResponse['data'] }) => response.data,
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Store', id },
        { type: 'Store', id: 'LIST' },
      ],
    }),
  }),
});

export const {
  useGetStoresQuery,
  useGetStoreQuery,
  useCreateStoreMutation,
  useUpdateStoreStatusMutation,
} = storesApi;
