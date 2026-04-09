import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';
import type {
  AdjustInventoryPayload,
  CreateWarehousePayload,
  InventoryFilters,
  InventoryRecord,
  PaginatedResponse,
  StockAlert,
  StockAlertFilters,
  StockMovement,
  StockMovementFilters,
  TransferInventoryPayload,
  UpdateWarehousePayload,
  Warehouse,
  WarehouseFilters,
} from '../types/inventory';

export const inventoryApi = createApi({
  reducerPath: 'inventoryApi',
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
  tagTypes: ['Warehouse', 'Inventory', 'StockMovement', 'StockAlert'],
  endpoints: (builder) => ({
    getWarehouses: builder.query<PaginatedResponse<Warehouse>, WarehouseFilters | undefined>({
      query: (filters = {}) => {
        const params = new URLSearchParams();

        if (filters.page) params.append('page', filters.page.toString());
        if (filters.per_page) params.append('per_page', filters.per_page.toString());
        if (filters.is_active !== undefined) params.append('is_active', filters.is_active ? '1' : '0');
        if (filters.sort_by) params.append('sort_by', filters.sort_by);
        if (filters.sort_order) params.append('sort_order', filters.sort_order);

        return `/warehouses?${params.toString()}`;
      },
      providesTags: (result) =>
        result
          ? [
              ...result.data.map(({ id }) => ({ type: 'Warehouse' as const, id })),
              { type: 'Warehouse', id: 'LIST' },
            ]
          : [{ type: 'Warehouse', id: 'LIST' }],
    }),

    createWarehouse: builder.mutation<{ data: Warehouse }, CreateWarehousePayload>({
      query: (payload) => ({
        url: '/warehouses',
        method: 'POST',
        body: payload,
      }),
      invalidatesTags: [{ type: 'Warehouse', id: 'LIST' }],
    }),

    updateWarehouse: builder.mutation<{ data: Warehouse }, UpdateWarehousePayload>({
      query: ({ id, ...payload }) => ({
        url: `/warehouses/${id}`,
        method: 'PATCH',
        body: payload,
      }),
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Warehouse', id },
        { type: 'Warehouse', id: 'LIST' },
      ],
    }),

    deleteWarehouse: builder.mutation<{ message: string }, number>({
      query: (id) => ({
        url: `/warehouses/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: [{ type: 'Warehouse', id: 'LIST' }],
    }),

    setDefaultWarehouse: builder.mutation<{ message: string; data: Warehouse }, number>({
      query: (id) => ({
        url: `/warehouses/${id}/set-default`,
        method: 'PATCH',
      }),
      invalidatesTags: [{ type: 'Warehouse', id: 'LIST' }],
    }),

    getInventory: builder.query<PaginatedResponse<InventoryRecord>, InventoryFilters | undefined>({
      query: (filters = {}) => {
        const params = new URLSearchParams();

        if (filters.page) params.append('page', filters.page.toString());
        if (filters.per_page) params.append('per_page', filters.per_page.toString());
        if (filters.product_id) params.append('product_id', filters.product_id.toString());
        if (filters.warehouse_id) params.append('warehouse_id', filters.warehouse_id.toString());
        if (filters.low_stock !== undefined) params.append('low_stock', filters.low_stock ? '1' : '0');
        if (filters.out_of_stock !== undefined) params.append('out_of_stock', filters.out_of_stock ? '1' : '0');
        if (filters.sort_by) params.append('sort_by', filters.sort_by);
        if (filters.sort_order) params.append('sort_order', filters.sort_order);

        return `/inventory?${params.toString()}`;
      },
      providesTags: (result) =>
        result
          ? [
              ...result.data.map(({ id }) => ({ type: 'Inventory' as const, id })),
              { type: 'Inventory', id: 'LIST' },
            ]
          : [{ type: 'Inventory', id: 'LIST' }],
    }),

    adjustInventory: builder.mutation<{ data: InventoryRecord }, AdjustInventoryPayload>({
      query: (payload) => ({
        url: '/inventory/adjust',
        method: 'POST',
        body: payload,
      }),
      invalidatesTags: [
        { type: 'Inventory', id: 'LIST' },
        { type: 'StockMovement', id: 'LIST' },
      ],
    }),

    transferInventory: builder.mutation<
      { from: InventoryRecord; to: InventoryRecord },
      TransferInventoryPayload
    >({
      query: (payload) => ({
        url: '/inventory/transfer',
        method: 'POST',
        body: payload,
      }),
      invalidatesTags: [
        { type: 'Inventory', id: 'LIST' },
        { type: 'StockMovement', id: 'LIST' },
      ],
    }),

    getStockMovements: builder.query<PaginatedResponse<StockMovement>, StockMovementFilters | undefined>({
      query: (filters = {}) => {
        const params = new URLSearchParams();

        if (filters.page) params.append('page', filters.page.toString());
        if (filters.per_page) params.append('per_page', filters.per_page.toString());
        if (filters.inventory_id) params.append('inventory_id', filters.inventory_id.toString());
        if (filters.type) params.append('type', filters.type);

        return `/inventory/movements?${params.toString()}`;
      },
      providesTags: (result) =>
        result
          ? [
              ...result.data.map(({ id }) => ({ type: 'StockMovement' as const, id })),
              { type: 'StockMovement', id: 'LIST' },
            ]
          : [{ type: 'StockMovement', id: 'LIST' }],
    }),

    getStockAlerts: builder.query<PaginatedResponse<StockAlert>, StockAlertFilters | undefined>({
      query: (filters = {}) => {
        const params = new URLSearchParams();

        if (filters.page) params.append('page', filters.page.toString());
        if (filters.per_page) params.append('per_page', filters.per_page.toString());
        if (filters.status) params.append('status', filters.status);
        if (filters.alert_type) params.append('alert_type', filters.alert_type);

        return `/stock-alerts?${params.toString()}`;
      },
      providesTags: (result) =>
        result
          ? [
              ...result.data.map(({ id }) => ({ type: 'StockAlert' as const, id })),
              { type: 'StockAlert', id: 'LIST' },
            ]
          : [{ type: 'StockAlert', id: 'LIST' }],
    }),

    resolveStockAlert: builder.mutation<{ message: string; data: StockAlert }, number>({
      query: (id) => ({
        url: `/stock-alerts/${id}/resolve`,
        method: 'PATCH',
      }),
      invalidatesTags: [{ type: 'StockAlert', id: 'LIST' }, { type: 'Inventory', id: 'LIST' }],
    }),
  }),
});

export const {
  useGetWarehousesQuery,
  useCreateWarehouseMutation,
  useUpdateWarehouseMutation,
  useDeleteWarehouseMutation,
  useSetDefaultWarehouseMutation,
  useGetInventoryQuery,
  useAdjustInventoryMutation,
  useTransferInventoryMutation,
  useGetStockMovementsQuery,
  useGetStockAlertsQuery,
  useResolveStockAlertMutation,
} = inventoryApi;
