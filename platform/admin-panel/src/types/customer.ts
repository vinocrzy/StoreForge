/**
 * Customer Types for E-Commerce Admin Panel
 */

// Customer interface
export interface Customer {
  id: number;
  store_id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  status: 'active' | 'inactive' | 'blocked';
  total_orders: number;
  total_spent: string | number;
  average_order_value: string | number;
  last_order_at?: string | null;
  notes?: string | null;
  metadata?: Record<string, any> | null;
  created_at: string;
  updated_at: string;
  
  // Computed properties
  full_name?: string;
}

// Customer filters
export interface CustomerFilters {
  page?: number;
  per_page?: number;
  status?: 'active' | 'inactive' | 'blocked';
  search?: string;
}

// Create customer DTO
export interface CreateCustomerData {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  password?: string;
  status?: 'active' | 'inactive' | 'blocked';
  notes?: string;
}

// Update customer DTO
export interface UpdateCustomerData {
  first_name?: string;
  last_name?: string;
  email?: string;
  phone?: string;
  password?: string;
  status?: 'active' | 'inactive' | 'blocked';
  notes?: string;
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
}

export interface CustomerResponse {
  data: Customer;
}
