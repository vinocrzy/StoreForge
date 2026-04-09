export interface Warehouse {
  id: number;
  store_id: number;
  name: string;
  code: string;
  address: string | null;
  city: string | null;
  state: string | null;
  postal_code: string | null;
  country: string | null;
  is_active: boolean;
  is_default?: boolean;
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
  inventory_count?: number;
}

export interface InventoryProduct {
  id: number;
  name: string;
  sku: string;
}

export interface InventoryWarehouse {
  id: number;
  name: string;
  code: string;
}

export interface InventoryRecord {
  id: number;
  store_id: number;
  product_id: number;
  variant_id: number | null;
  warehouse_id: number;
  quantity: number;
  reserved_quantity: number;
  available_quantity: number;
  low_stock_threshold: number;
  created_at: string;
  updated_at: string;
  product?: InventoryProduct;
  warehouse?: InventoryWarehouse;
}

export interface StockMovementUser {
  id: number;
  first_name?: string;
  last_name?: string;
  name?: string;
  email?: string;
}

export interface StockMovement {
  id: number;
  store_id: number;
  inventory_id: number;
  type: string;
  quantity: number;
  notes: string | null;
  user_id: number | null;
  created_at: string;
  inventory?: InventoryRecord;
  user?: StockMovementUser | null;
}

export interface StockAlert {
  id: number;
  store_id: number;
  product_id: number;
  warehouse_id: number | null;
  alert_type: 'low_stock' | 'out_of_stock';
  threshold: number;
  current_quantity: number;
  status: 'active' | 'resolved';
  resolved_at: string | null;
  created_at: string;
  updated_at: string;
  product?: InventoryProduct;
  warehouse?: InventoryWarehouse;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
  links: PaginationLinks;
}

export interface WarehouseFilters {
  page?: number;
  per_page?: number;
  is_active?: boolean;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}

export interface InventoryFilters {
  page?: number;
  per_page?: number;
  product_id?: number;
  warehouse_id?: number;
  low_stock?: boolean;
  out_of_stock?: boolean;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}

export interface StockMovementFilters {
  page?: number;
  per_page?: number;
  inventory_id?: number;
  type?: string;
}

export interface StockAlertFilters {
  page?: number;
  per_page?: number;
  status?: 'active' | 'resolved';
  alert_type?: 'low_stock' | 'out_of_stock';
}

export interface CreateWarehousePayload {
  name: string;
  code: string;
  address?: string;
  city?: string;
  state?: string;
  postal_code?: string;
  country?: string;
  is_active?: boolean;
}

export interface UpdateWarehousePayload extends Partial<CreateWarehousePayload> {
  id: number;
}

export interface AdjustInventoryPayload {
  product_id: number;
  variant_id?: number | null;
  warehouse_id: number;
  quantity: number;
  type: 'purchase' | 'sale' | 'return' | 'adjustment' | 'damage' | 'lost';
  notes?: string;
}

export interface TransferInventoryPayload {
  product_id: number;
  variant_id?: number | null;
  from_warehouse_id: number;
  to_warehouse_id: number;
  quantity: number;
  notes?: string;
}
