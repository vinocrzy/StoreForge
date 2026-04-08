/**
 * Store Types for Admin Panel
 * Super admin management of multi-tenant stores
 */

export interface Store {
  id: number;
  name: string;
  slug: string;
  domain: string | null;
  logo_url: string | null;
  status: 'active' | 'inactive' | 'suspended';
  settings: StoreSettings;
  created_at: string;
  updated_at: string;
}

export interface StoreSettings {
  currency: string;
  timezone: string;
  language: string;
  theme?: StoreTheme;
  contact?: StoreContact;
  social?: StoreSocial;
}

export interface StoreTheme {
  primary_color?: string;
  secondary_color?: string;
  font_family?: string;
  logo_width?: number;
  logo_height?: number;
}

export interface StoreContact {
  email?: string;
  phone?: string;
  address?: string;
}

export interface StoreSocial {
  facebook?: string;
  instagram?: string;
  twitter?: string;
}

export interface CreateStoreData {
  name: string;
  slug: string;
  domain?: string;
  settings?: Partial<StoreSettings>;
}

export interface UpdateStoreData {
  name?: string;
  slug?: string;
  domain?: string;
  status?: 'active' | 'inactive' | 'suspended';
  settings?: Partial<StoreSettings>;
  logo_url?: string;
}

export interface StoreStatistics {
  total_products: number;
  total_orders: number;
  total_customers: number;
  revenue: number;
  orders_this_month: number;
  revenue_this_month: number;
}

export interface StoresResponse {
  data: Store[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface StoreResponse {
  data: Store;
}

export interface StoreFilters {
  search?: string;
  status?: 'active' | 'inactive' | 'suspended';
  page?: number;
  per_page?: number;
}
