/**
 * Stores API Service
 * RTK Query endpoints for store management (super admin)
 */

import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';
import type {
  Store,
  StoresResponse,
  StoreResponse,
  CreateStoreData,
  UpdateStoreData,
  StoreFilters,
  StoreStatistics,
} from '../types/store';

export const storesApi = createApi({
  reducerPath: 'storesApi',
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
    getStore: builder.query<Store, number>({
      query: (id) => `/stores/${id}`,
      transformResponse: (response: StoreResponse) => response.data,
      providesTags: (_result, _error, id) => [{ type: 'Store', id }],
    }),

    // Create store
    createStore: builder.mutation<Store, CreateStoreData>({
      query: (data) => ({
        url: '/stores',
        method: 'POST',
        body: data,
      }),
      transformResponse: (response: StoreResponse) => response.data,
      invalidatesTags: [{ type: 'Store', id: 'LIST' }],
    }),

    // Update store
    updateStore: builder.mutation<Store, { id: number; data: UpdateStoreData }>({
      query: ({ id, data }) => ({
        url: `/stores/${id}`,
        method: 'PUT',
        body: data,
      }),
      transformResponse: (response: StoreResponse) => response.data,
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Store', id },
        { type: 'Store', id: 'LIST' },
      ],
    }),

    // Update store settings
    updateStoreSettings: builder.mutation<Store, { id: number; settings: any }>({
      query: ({ id, settings }) => ({
        url: `/stores/${id}/settings`,
        method: 'PUT',
        body: { settings },
      }),
      transformResponse: (response: StoreResponse) => response.data,
      invalidatesTags: (_result, _error, { id }) => [{ type: 'Store', id }],
    }),

    // Get store statistics
    getStoreStatistics: builder.query<StoreStatistics, number>({
      query: (id) => `/stores/${id}/statistics`,
      transformResponse: (response: { data: StoreStatistics }) => response.data,
    }),

    // Delete store (soft delete)
    deleteStore: builder.mutation<void, number>({
      query: (id) => ({
        url: `/stores/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: [{ type: 'Store', id: 'LIST' }],
    }),
  }),
});

export const {
  useGetStoresQuery,
  useGetStoreQuery,
  useCreateStoreMutation,
  useUpdateStoreMutation,
  useUpdateStoreSettingsMutation,
  useGetStoreStatisticsQuery,
  useDeleteStoreMutation,
} = storesApi;
