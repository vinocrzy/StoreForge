/**
 * Store Types for Admin Panel
 * Super admin management of multi-tenant stores
 */

export interface Store {
  id: number;
  name: string;
  slug: string;
  domain: string | null;
  email?: string | null;
  phone?: string | null;
  currency?: string;
  timezone?: string;
  language?: string;
  logo_url: string | null;
  status: 'active' | 'inactive' | 'suspended';
  settings?: StoreSettings;
  users?: StoreUser[];
  created_at: string;
  updated_at: string;
}

export interface StoreUser {
  id: number;
  name: string;
  email: string;
  phone?: string;
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
  status?: 'active' | 'inactive' | 'suspended';
  email?: string;
  phone?: string;
  currency?: string;
  timezone?: string;
  language?: string;
  owner_name: string;
  owner_phone: string;
  owner_email?: string;
  owner_password: string;
  settings?: Partial<StoreSettings>;
}

export interface UpdateStoreStatusData {
  status?: 'active' | 'inactive' | 'suspended';
}

export interface StoreMetrics {
  products_count: number;
  orders_count: number;
  customers_count: number;
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

export interface StoreDetailsResponse {
  data: Store;
  meta: StoreMetrics;
}

export interface StoreCreateResponse {
  data: {
    store: Store;
    owner: StoreUser;
  };
}

export interface StoreFilters {
  search?: string;
  status?: 'active' | 'inactive' | 'suspended';
  page?: number;
  per_page?: number;
}
