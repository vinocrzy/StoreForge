import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';

export interface ShippingMethod {
  id: number;
  store_id: number;
  name: string;
  type: 'flat_rate' | 'weight_based' | 'free_above' | 'local_pickup';
  rate: number | null;
  free_above: number | null;
  config: Record<string, unknown> | null;
  is_active: boolean;
  display_order: number;
  created_at: string;
  updated_at: string;
}

export interface ShippingMethodPayload {
  name: string;
  type: ShippingMethod['type'];
  rate?: number | null;
  free_above?: number | null;
  config?: Record<string, unknown>;
  is_active?: boolean;
  display_order?: number;
}

export interface TaxSettings {
  tax_enabled: boolean;
  tax_rate: number;
  tax_display: 'inclusive' | 'exclusive';
  tax_label: string;
  category_tax_rates: Record<string, number>;
}

export const shippingApi = createApi({
  reducerPath: 'shippingApi',
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
  tagTypes: ['ShippingMethods', 'TaxSettings'],
  endpoints: (builder) => ({
    getShippingMethods: builder.query<{ data: ShippingMethod[] }, void>({
      query: () => '/admin/shipping-methods',
      providesTags: ['ShippingMethods'],
    }),
    createShippingMethod: builder.mutation<{ data: ShippingMethod }, ShippingMethodPayload>({
      query: (body) => ({ url: '/admin/shipping-methods', method: 'POST', body }),
      invalidatesTags: ['ShippingMethods'],
    }),
    updateShippingMethod: builder.mutation<{ data: ShippingMethod }, { id: number } & Partial<ShippingMethodPayload>>({
      query: ({ id, ...body }) => ({ url: `/admin/shipping-methods/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ShippingMethods'],
    }),
    deleteShippingMethod: builder.mutation<void, number>({
      query: (id) => ({ url: `/admin/shipping-methods/${id}`, method: 'DELETE' }),
      invalidatesTags: ['ShippingMethods'],
    }),
    getTaxSettings: builder.query<{ data: TaxSettings }, void>({
      query: () => '/admin/settings/tax',
      providesTags: ['TaxSettings'],
    }),
    updateTaxSettings: builder.mutation<{ data: TaxSettings; message: string }, Partial<TaxSettings>>({
      query: (body) => ({ url: '/admin/settings/tax', method: 'PUT', body }),
      invalidatesTags: ['TaxSettings'],
    }),
  }),
});

export const {
  useGetShippingMethodsQuery,
  useCreateShippingMethodMutation,
  useUpdateShippingMethodMutation,
  useDeleteShippingMethodMutation,
  useGetTaxSettingsQuery,
  useUpdateTaxSettingsMutation,
} = shippingApi;
