/**
 * Order Types for E-Commerce Admin Panel
 */

import type { Customer } from './customer';

// Order status enums
export type OrderStatus = 
  | 'pending' 
  | 'confirmed' 
  | 'processing' 
  | 'shipped' 
  | 'delivered' 
  | 'cancelled' 
  | 'refunded';

export type PaymentStatus = 
  | 'pending' 
  | 'paid' 
  | 'failed' 
  | 'refunded' 
  | 'partially_refunded';

export type FulfillmentStatus = 
  | 'unfulfilled' 
  | 'partial' 
  | 'fulfilled';

export type PaymentMethod = 
  | 'manual' 
  | 'bank_transfer' 
  | 'cash' 
  | 'stripe' 
  | 'paypal' 
  | 'razorpay';

// Order interface
export interface Order {
  id: number;
  store_id: number;
  customer_id: number;
  order_number: string;
  status: OrderStatus;
  payment_status: PaymentStatus;
  fulfillment_status: FulfillmentStatus;
  currency: string;
  subtotal: string | number;
  discount_amount: string | number;
  shipping_amount: string | number;
  tax_amount: string | number;
  total: string | number;
  coupon_code?: string | null;
  customer_note?: string | null;
  admin_note?: string | null;
  payment_method?: string | null;
  paid_at?: string | null;
  paid_by_user_id?: number | null;
  payment_notes?: string | null;
  payment_proof_url?: string | null;
  billing_address_id?: number | null;
  shipping_address_id?: number | null;
  ip_address?: string | null;
  user_agent?: string | null;
  placed_at?: string | null;
  confirmed_at?: string | null;
  shipped_at?: string | null;
  delivered_at?: string | null;
  cancelled_at?: string | null;
  created_at: string;
  updated_at: string;
  
  // Relationships (populated when included)
  customer?: Customer;
  items?: OrderItem[];
  payments?: Payment[];
  billing_address?: CustomerAddress;
  shipping_address?: CustomerAddress;
}

// Order item interface
export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  product_variant_id?: number | null;
  quantity: number;
  price: string | number;
  discount_amount: string | number;
  tax_amount: string | number;
  total: string | number;
  product_snapshot: ProductSnapshot;
  created_at: string;
  updated_at: string;
  
  // Relationships
  product?: Product;
  variant?: ProductVariant;
}

// Product snapshot (product details at time of order)
export interface ProductSnapshot {
  id: number;
  name: string;
  sku: string;
  slug?: string;
  price?: number;
  image?: string;
  variant_name?: string;
}

// Payment interface
export interface Payment {
  id: number;
  store_id: number;
  order_id: number;
  gateway: string;
  amount: string | number;
  currency: string;
  status: 'pending' | 'completed' | 'failed' | 'refunded';
  transaction_id?: string | null;
  payment_method?: string | null;
  payment_notes?: string | null;
  metadata?: Record<string, any> | null;
  paid_at?: string | null;
  failed_at?: string | null;
  refunded_at?: string | null;
  created_at: string;
  updated_at: string;
}

// Customer address interface
export interface CustomerAddress {
  id: number;
  customer_id: number;
  label?: string | null;
  first_name: string;
  last_name: string;
  phone: string;
  address_line1: string;
  address_line2?: string | null;
  city: string;
  state: string;
  postal_code: string;
  country: string;
  is_default: boolean;
  created_at: string;
  updated_at: string;
}

// Simplified product and variant types (for order items)
interface Product {
  id: number;
  name: string;
  sku: string;
  slug: string;
  price: number;
  images?: Array<{ url: string; is_primary: boolean }>;
}

interface ProductVariant {
  id: number;
  sku: string;
  name: string;
  price: number;
}

// Order filters for list page
export interface OrderFilters {
  page?: number;
  per_page?: number;
  status?: OrderStatus;
  payment_status?: PaymentStatus;
  fulfillment_status?: FulfillmentStatus;
  customer_id?: number;
  search?: string;
}

// Order statistics
export interface OrderStatistics {
  total_orders: number;
  pending_orders: number;
  processing_orders: number;
  shipped_orders: number;
  delivered_orders: number;
  cancelled_orders: number;
  total_revenue: string | number;
  pending_payments: string | number;
}

// Create order DTO
export interface CreateOrderData {
  customer_id: number;
  items: Array<{
    product_id: number;
    product_variant_id?: number | null;
    quantity: number;
    price?: number;
    discount_amount?: number;
  }>;
  customer_note?: string;
  admin_note?: string;
  coupon_code?: string;
  payment_method?: string;
  shipping_amount?: number;
  billing_address_id?: number;
  shipping_address_id?: number;
}

// Update order DTO
export interface UpdateOrderData {
  customer_note?: string;
  admin_note?: string;
  shipping_amount?: number;
}

// Update status DTO
export interface UpdateOrderStatusData {
  status: OrderStatus;
}

// Cancel order DTO
export interface CancelOrderData {
  reason?: string;
}

// Record payment DTO
export interface RecordPaymentData {
  payment_method: string;
  amount: number;
  transaction_id?: string;
  payment_notes?: string;
  metadata?: Record<string, any>;
}

// API response types
export interface OrdersResponse {
  data: Order[];
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

export interface OrderResponse {
  data: Order;
}

export interface OrderStatisticsResponse {
  data: OrderStatistics;
}

export interface PaymentResponse {
  data: Payment;
}
