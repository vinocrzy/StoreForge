import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { RootState } from '../store';

export interface LoyaltyConfig {
  enabled: boolean;
  points_per_dollar: number;
  redemption_threshold: number;
  points_to_dollar_rate: number;
  program_name: string;
}

export interface LoyaltyPointEntry {
  id: number;
  points: number;
  type: string;
  description: string;
  balance_after: number;
  created_at: string;
}

export interface CurrencySettings {
  base_currency: string;
  enabled_currencies: string[];
  exchange_rates: Record<string, number>;
}

export interface EmailMarketingSettings {
  enabled: boolean;
  api_key_set: boolean;
  list_id: string | null;
  subscriber_count: number;
}

export const settingsExtApi = createApi({
  reducerPath: 'settingsExtApi',
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
  tagTypes: ['LoyaltyConfig', 'CurrencySettings', 'EmailMarketing'],
  endpoints: (builder) => ({
    getLoyaltyConfig: builder.query<{ data: LoyaltyConfig }, void>({
      query: () => '/admin/loyalty/config',
      providesTags: ['LoyaltyConfig'],
    }),
    updateLoyaltyConfig: builder.mutation<{ data: LoyaltyConfig; message: string }, Partial<LoyaltyConfig>>({
      query: (body) => ({ url: '/admin/loyalty/config', method: 'PUT', body }),
      invalidatesTags: ['LoyaltyConfig'],
    }),
    getCurrencySettings: builder.query<{ data: CurrencySettings }, void>({
      query: () => '/admin/settings/currency',
      providesTags: ['CurrencySettings'],
    }),
    updateCurrencySettings: builder.mutation<{ data: CurrencySettings; message: string }, { base_currency: string; exchange_rates: Record<string, number> }>({
      query: (body) => ({ url: '/admin/settings/currency', method: 'PUT', body }),
      invalidatesTags: ['CurrencySettings'],
    }),
    getEmailMarketingSettings: builder.query<{ data: EmailMarketingSettings }, void>({
      query: () => '/admin/settings/email-marketing',
      providesTags: ['EmailMarketing'],
    }),
    updateEmailMarketingSettings: builder.mutation<{ message: string }, { enabled?: boolean; mailchimp_api_key?: string; mailchimp_list_id?: string }>({
      query: (body) => ({ url: '/admin/settings/email-marketing', method: 'PUT', body }),
      invalidatesTags: ['EmailMarketing'],
    }),
  }),
});

export const {
  useGetLoyaltyConfigQuery,
  useUpdateLoyaltyConfigMutation,
  useGetCurrencySettingsQuery,
  useUpdateCurrencySettingsMutation,
  useGetEmailMarketingSettingsQuery,
  useUpdateEmailMarketingSettingsMutation,
} = settingsExtApi;
