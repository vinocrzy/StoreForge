import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';

export interface SettingField {
  value: string | number | boolean | null;
  type: 'string' | 'integer' | 'boolean' | 'json';
  description: string | null;
  is_public: boolean;
}

export type SettingsGroup = Record<string, SettingField>;

export interface AllSettings {
  general: SettingsGroup;
  branding: SettingsGroup;
  policies: SettingsGroup;
  checkout: SettingsGroup;
  payments: SettingsGroup;
  shipping: SettingsGroup;
  seo: SettingsGroup;
  notifications: SettingsGroup;
  security: SettingsGroup;
}

export type SettingsGroupName = keyof AllSettings;

export type UpdateSettingsPayload = Partial<Record<SettingsGroupName, Record<string, string | number | boolean>>>;

export const settingsApi = createApi({
  reducerPath: 'settingsApi',
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
  tagTypes: ['Settings'],
  endpoints: (builder) => ({
    getAllSettings: builder.query<{ data: AllSettings }, void>({
      query: () => '/settings',
      providesTags: ['Settings'],
    }),

    getSettingsGroup: builder.query<{ data: SettingsGroup }, SettingsGroupName>({
      query: (group) => `/settings/${group}`,
      providesTags: (_result, _error, group) => [{ type: 'Settings', id: group }],
    }),

    updateSettings: builder.mutation<{ message: string; data: AllSettings }, UpdateSettingsPayload>({
      query: (body) => ({
        url: '/settings',
        method: 'PATCH',
        body,
      }),
      invalidatesTags: ['Settings'],
    }),
  }),
});

export const {
  useGetAllSettingsQuery,
  useGetSettingsGroupQuery,
  useUpdateSettingsMutation,
} = settingsApi;
