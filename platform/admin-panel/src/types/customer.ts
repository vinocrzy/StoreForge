/**
 * Customer Types for E-Commerce Admin Panel
 */

// Customer status enum
export type CustomerStatus = 'active' | 'inactive' | 'banned';

// Gender enum
export type Gender = 'male' | 'female' | 'other' | 'prefer_not_to_say';

// Customer interface
export interface Customer {
  id: number;
  store_id: number;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string;
  status: CustomerStatus;
  date_of_birth: string | null;
  gender: Gender | null;
  notes: string | null;
  metadata: Record<string, any> | null;
  email_verified_at: string | null;
  phone_verified_at: string | null;
  last_login_at: string | null;
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
  
  // Computed properties
  full_name?: string;
  
  // Relationships (loaded when included)
  addresses?: CustomerAddress[];
  default_address?: CustomerAddress | null;
  orders?: any[]; // Reference to orders if needed
}

// Customer address interface
export interface CustomerAddress {
  id: number;
  customer_id: number;
  type: 'billing' | 'shipping' | 'both';
  label: string | null;
  first_name: string;
  last_name: string;
  company: string | null;
  address_line1: string;
  address_line2: string | null;
  city: string;
  state_province: string;
  postal_code: string;
  country: string;
  phone: string;
  is_default: boolean;
  created_at: string;
  updated_at: string;
}

// Customer filters
export interface CustomerFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: CustomerStatus;
  is_active?: boolean;
  email_verified?: boolean;
  phone_verified?: boolean;
  sort_by?: 'created_at' | 'first_name' | 'last_name' | 'email' | 'phone' | 'last_login_at';
  sort_order?: 'asc' | 'desc';
}

// Create customer DTO
export interface CreateCustomerData {
  first_name: string;
  last_name: string;
  phone: string;
  email?: string;
  password: string;
  status?: CustomerStatus;
  date_of_birth?: string;
  gender?: Gender;
  notes?: string;
  address?: CreateCustomerAddressData;
}

// Update customer DTO
export interface UpdateCustomerData {
  first_name?: string;
  last_name?: string;
  phone?: string;
  email?: string;
  password?: string;
  status?: CustomerStatus;
  date_of_birth?: string;
  gender?: Gender;
  notes?: string;
}

// Update status DTO
export interface UpdateCustomerStatusData {
  status: CustomerStatus;
}

// Customer address DTO
export interface CreateCustomerAddressData {
  type: 'billing' | 'shipping' | 'both';
  label?: string;
  first_name: string;
  last_name: string;
  company?: string;
  address_line1: string;
  address_line2?: string;
  city: string;
  state_province: string;
  postal_code: string;
  country: string;
  phone: string;
  is_default?: boolean;
}

// API response types
export interface CustomersResponse {
  data: Customer[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
  links?: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

export interface CustomerResponse {
  data: Customer;
}

export interface CustomerStatistics {
  total_customers: number;
  active_customers: number;
  inactive_customers: number;
  banned_customers: number;
  new_customers_this_month: number;
  total_revenue: number;
  average_order_value: number;
}
