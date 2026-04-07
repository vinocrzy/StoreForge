// Product API types
export interface Product {
  id: number;
  store_id: number;
  name: string;
  slug: string;
  sku: string;
  description: string | null;
  short_description: string | null;
  price: number | string;  // DB returns as string
  compare_price: number | string | null;  // DB returns as string
  cost_price: number | string | null;  // DB returns as string
  stock_quantity: number;
  low_stock_threshold: number;
  weight: number | string | null;  // DB returns as string
  length: number | null;
  width: number | null;
  height: number | null;
  is_featured: boolean;
  is_taxable: boolean;
  status: 'active' | 'draft' | 'archived';
  published_at: string | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  categories?: Category[];
  images?: ProductImage[];
  variants?: ProductVariant[];
  primary_image?: ProductImage;
}

export interface Category {
  id: number;
  store_id: number;
  parent_id: number | null;
  name: string;
  slug: string;
  description: string | null;
  image_url: string | null;
  sort_order: number;
  is_active: boolean;
  products_count?: number;
  created_at: string;
  updated_at: string;
  children?: Category[];
  parent?: Category;
}

export interface ProductImage {
  id: number;
  product_id: number;
  store_id: number;
  url: string;
  alt_text: string | null;
  sort_order: number;
  is_primary: boolean;
  created_at: string;
  updated_at: string;
}

export interface ProductVariant {
  id: number;
  product_id: number;
  store_id: number;
  sku: string;
  name: string;
  price: number | null;
  stock_quantity: number;
  attributes: Record<string, string>;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface ProductsResponse {
  data: Product[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
}

export interface CategoriesResponse {
  data: Category[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface ProductFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: 'active' | 'draft' | 'archived';
  category_id?: number;
  is_featured?: boolean;
  stock_status?: 'in_stock' | 'low_stock' | 'out_of_stock';
}

export interface CreateProductData {
  name: string;
  description?: string;
  short_description?: string;
  price: number;
  compare_price?: number;
  cost_price?: number;
  sku: string;
  stock_quantity: number;
  low_stock_threshold?: number;
  weight?: number;
  length?: number;
  width?: number;
  height?: number;
  is_featured?: boolean;
  is_taxable?: boolean;
  status: 'active' | 'draft' | 'archived';
  category_ids?: number[];
}

export interface UpdateProductData extends Partial<CreateProductData> {
  id: number;
}

export interface StockUpdateData {
  operation: 'set' | 'increment' | 'decrement';
  quantity: number;
  reason?: string;
}
