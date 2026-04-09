/**
 * Customers API Service
 * RTK Query endpoints for customer management
 */

import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';
import type {
  Customer,
  CustomersResponse,
  CustomerResponse,
  CustomerFilters,
  CreateCustomerData,
  UpdateCustomerData,
  UpdateCustomerStatusData,
} from '../types/customer';

export const customersApi = createApi({
  reducerPath: 'customersApi',
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
  tagTypes: ['Customer'],
  endpoints: (builder) => ({
    // Get customers list with filters
    getCustomers: builder.query<CustomersResponse, CustomerFilters | void>({
      query: (filters = {}) => ({
        url: '/customers',
        params: filters || {},
      }),
      providesTags: (result) =>
        result?.data
          ? [
              ...result.data.map(({ id }) => ({ type: 'Customer' as const, id })),
              { type: 'Customer', id: 'LIST' },
            ]
          : [{ type: 'Customer', id: 'LIST' }],
    }),

    // Get single customer by ID
    getCustomer: builder.query<Customer, number>({
      query: (id) => `/customers/${id}`,
      transformResponse: (response: CustomerResponse) => response.data,
      providesTags: (_result, _error, id) => [{ type: 'Customer', id }],
    }),

    // Create new customer
    createCustomer: builder.mutation<Customer, CreateCustomerData>({
      query: (data) => ({
        url: '/customers',
        method: 'POST',
        body: data,
      }),
      transformResponse: (response: CustomerResponse) => response.data,
      invalidatesTags: [{ type: 'Customer', id: 'LIST' }],
    }),

    // Update customer
    updateCustomer: builder.mutation<Customer, { id: number; data: UpdateCustomerData }>({
      query: ({ id, data }) => ({
        url: `/customers/${id}`,
        method: 'PUT',
        body: data,
      }),
      transformResponse: (response: CustomerResponse) => response.data,
      invalidatesTags: (_result, _error, { id }) => [{ type: 'Customer', id }],
    }),

    // Delete customer (soft delete)
    deleteCustomer: builder.mutation<void, number>({
      query: (id) => ({
        url: `/customers/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: (_result, _error, id) => [
        { type: 'Customer', id },
        { type: 'Customer', id: 'LIST' },
      ],
    }),

    // Update customer status
    updateCustomerStatus: builder.mutation<Customer, { id: number; data: UpdateCustomerStatusData }>({
      query: ({ id, data }) => ({
        url: `/customers/${id}/status`,
        method: 'POST',
        body: data,
      }),
      transformResponse: (response: CustomerResponse) => response.data,
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Customer', id },
        { type: 'Customer', id: 'LIST' },
      ],
    }),

    // Verify email
    verifyEmail: builder.mutation<Customer, number>({
      query: (id) => ({
        url: `/customers/${id}/verify-email`,
        method: 'POST',
      }),
      transformResponse: (response: CustomerResponse) => response.data,
      invalidatesTags: (_result, _error, id) => [{ type: 'Customer', id }],
    }),

    // Verify phone
    verifyPhone: builder.mutation<Customer, number>({
      query: (id) => ({
        url: `/customers/${id}/verify-phone`,
        method: 'POST',
      }),
      transformResponse: (response: CustomerResponse) => response.data,
      invalidatesTags: (_result, _error, id) => [{ type: 'Customer', id }],
    }),

    // Export customers to CSV
    exportCustomersCsv: builder.mutation<Blob, Partial<CustomerFilters>>({
      query: (filters = {}) => ({
        url: '/customers/export',
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
  useGetCustomersQuery,
  useGetCustomerQuery,
  useCreateCustomerMutation,
  useUpdateCustomerMutation,
  useDeleteCustomerMutation,
  useUpdateCustomerStatusMutation,
  useVerifyEmailMutation,
  useVerifyPhoneMutation,
  useExportCustomersCsvMutation,
} = customersApi;
