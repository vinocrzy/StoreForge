# E-Commerce Platform API Integration Skill

## Purpose

This skill helps developers integrate with the E-Commerce Platform REST API. Use this when:
- Creating API service files
- Making API calls from frontend (React admin panel)
- Implementing API endpoints integration
- Debugging API integration issues
- Setting up authentication flows

## API Basics

**Base URL**: `http://localhost:8000/api/v1`  
**Authentication**: Bearer Token (Laravel Sanctum)  
**Tenant Isolation**: `X-Store-ID` header required for all authenticated endpoints

## Authentication Pattern

### Login (Phone-First)
```typescript
// RTK Query endpoint
login: builder.mutation<LoginResponse, LoginRequest>({
  query: (credentials) => ({
    url: '/auth/login',
    method: 'POST',
    body: credentials,
  }),
});

// Usage
const [login] = useLoginMutation();
await login({ 
  login: "+12025551234",  // or "email@example.com"
  password: "password" 
}).unwrap();
```

### Auto-Header Injection
```typescript
// In RTK Query baseQuery
prepareHeaders: (headers) => {
  const token = localStorage.getItem('auth_token');
  const storeId = localStorage.getItem('store_id');
  
  if (token) headers.set('Authorization', `Bearer ${token}`);
  if (storeId) headers.set('X-Store-ID', storeId);
  headers.set('Accept', 'application/json');
  
  return headers;
}
```

## Common API Patterns

### List Resources (with Pagination)
```typescript
// Endpoint definition
getProducts: builder.query<ProductsResponse, ProductsParams>({
  query: (params) => ({
    url: '/products',
    params: {
      page: params.page,
      per_page: params.perPage,
      search: params.search,
      status: params.status,
    },
  }),
});

// Usage
const { data, isLoading } = useGetProductsQuery({ 
  page: 1, 
  perPage: 20,
  status: 'active' 
});

// Response structure
{
  data: [...],        // Array of resources
  meta: {             // Pagination info
    current_page: 1,
    per_page: 20,
    total: 100
  },
  links: { ... }      // Pagination links
}
```

### Get Single Resource
```typescript
getProduct: builder.query<ProductResponse, number>({
  query: (id) => `/products/${id}`,
});

// Usage
const { data } = useGetProductQuery(productId);
```

### Create Resource
```typescript
createProduct: builder.mutation<Product, CreateProductRequest>({
  query: (product) => ({
    url: '/products',
    method: 'POST',
    body: product,
  }),
});

// Usage with error handling
const [createProduct] = useCreateProductMutation();

try {
  const result = await createProduct(productData).unwrap();
  message.success('Product created!');
} catch (error: any) {
  message.error(error?.data?.message || 'Failed to create product');
}
```

### Update Resource
```typescript
updateProduct: builder.mutation<Product, UpdateProductRequest>({
  query: ({ id, ...product }) => ({
    url: `/products/${id}`,
    method: 'PUT',
    body: product,
  }),
});
```

### Delete Resource
```typescript
deleteProduct: builder.mutation<void, number>({
  query: (id) => ({
    url: `/products/${id}`,
    method: 'DELETE',
  }),
});
```

## API Endpoints Quick Reference

### Authentication
- `POST /auth/login` - Login with phone or email
- `POST /auth/logout` - Logout current session
- `GET /auth/me` - Get authenticated user
- `POST /auth/revoke-all` - Revoke all tokens

### Profile (3 endpoints)
- `GET /profile` - Get authenticated user's profile
- `PATCH /profile` - Update authenticated user's profile
- `PATCH /profile/password` - Change authenticated user's password

### Products (14 endpoints)
- `GET /products` - List products (paginated, filterable)
- `POST /products` - Create product
- `GET /products/{id}` - Get product details
- `PUT /products/{id}` - Update product
- `DELETE /products/{id}` - Delete product
- `POST /products/{id}/stock` - Update stock

### Categories (8 endpoints)
- `GET /categories` - List categories
- `GET /categories/tree` - Get category tree
- `POST /categories` - Create category
- `PUT /categories/{id}` - Update category
- `POST /categories/reorder` - Reorder categories
- `POST /categories/{id}/move` - Move category

### Customers (15 endpoints)
- `GET /customers` - List customers
- `POST /customers` - Create customer
- `GET /customers/{id}` - Get customer
- `PUT /customers/{id}` - Update customer
- `POST /customers/{id}/status` - Update status
- `GET /customers/statistics` - Get statistics
- `GET /customers/{id}/addresses` - List addresses
- `POST /customers/{id}/addresses` - Create address

### Orders (10 endpoints)
- `GET /orders` - List orders
- `POST /orders` - Create order
- `GET /orders/{id}` - Get order details
- `POST /orders/{id}/status` - Update status
- `POST /orders/{id}/cancel` - Cancel order
- `POST /orders/{id}/payment` - Record payment
- `POST /orders/{id}/fulfill` - Fulfill order
- `GET /orders/statistics` - Order statistics

### Inventory (12 endpoints)
- `GET /inventory` - List inventory
- `GET /inventory/product/{id}` - Get by product
- `POST /inventory/adjust` - Adjust stock
- `POST /inventory/reserve` - Reserve stock
- `POST /inventory/release` - Release stock
- `POST /inventory/fulfill` - Fulfill stock
- `POST /inventory/transfer` - Transfer stock
- `GET /inventory/movements` - Stock movements
- `GET /stock-alerts` - List stock alerts
- `PATCH /stock-alerts/{id}/resolve` - Resolve stock alert

### Warehouses (6 endpoints)
- `GET /warehouses` - List warehouses
- `POST /warehouses` - Create warehouse
- `GET /warehouses/{id}` - Get warehouse
- `PUT /warehouses/{id}` - Update warehouse
- `DELETE /warehouses/{id}` - Delete warehouse
- `PATCH /warehouses/{id}/set-default` - Set default warehouse

### Stores (4 endpoints, Super Admin)
- `GET /stores` - List all stores
- `POST /stores` - Create store + owner account
- `GET /stores/{id}` - Get store details
- `PATCH /stores/{id}/status` - Activate/deactivate/suspend store

## Status Enums

### Order Status
```typescript
type OrderStatus = 
  | 'pending' 
  | 'confirmed' 
  | 'processing' 
  | 'shipped' 
  | 'delivered' 
  | 'cancelled' 
  | 'refunded';
```

### Payment Status
```typescript
type PaymentStatus = 
  | 'pending' 
  | 'paid' 
  | 'failed' 
  | 'refunded' 
  | 'partially_refunded';
```

### Product Status
```typescript
type ProductStatus = 'active' | 'draft' | 'archived';
```

## Error Handling

### Validation Errors (422)
```typescript
interface ValidationError {
  message: string;
  errors: Record<string, string[]>;
}

// Handle in component
try {
  await createProduct(data).unwrap();
} catch (error: any) {
  if (error.status === 422) {
    // Show validation errors
    Object.entries(error.data.errors).forEach(([field, messages]) => {
      messages.forEach((msg: string) => message.error(msg));
    });
  }
}
```

### Authentication Errors (401)
```typescript
// Handled automatically by axios interceptor
// Redirects to /login
```

## TypeScript Types

### Define API Response Types
```typescript
// Types
export interface Product {
  id: number;
  name: string;
  slug: string;
  sku: string;
  price: string;
  status: 'active' | 'draft' | 'archived';
  quantity: number;
  // ... other fields
}

export interface ProductsResponse {
  data: Product[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
  };
  links: {
    first: string;
    last: string;
    next: string | null;
    prev: string | null;
  };
}

export interface ProductsParams {
  page?: number;
  perPage?: number;
  search?: string;
  status?: 'active' | 'draft' | 'archived';
  categoryId?: number;
}
```

## Full Service File Template

```typescript
// services/products.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { Product, ProductsResponse, ProductsParams } from '../types/products';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';

export const productsApi = createApi({
  reducerPath: 'productsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: API_URL,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('auth_token');
      const storeId = localStorage.getItem('store_id');
      
      if (token) headers.set('Authorization', `Bearer ${token}`);
      if (storeId) headers.set('X-Store-ID', storeId);
      headers.set('Accept', 'application/json');
      
      return headers;
    },
  }),
  tagTypes: ['Product'],
  endpoints: (builder) => ({
    // List products
    getProducts: builder.query<ProductsResponse, ProductsParams>({
      query: (params) => ({
        url: '/products',
        params,
      }),
      providesTags: ['Product'],
    }),
    
    // Get single product
    getProduct: builder.query<{ data: Product }, number>({
      query: (id) => `/products/${id}`,
      providesTags: (result, error, id) => [{ type: 'Product', id }],
    }),
    
    // Create product
    createProduct: builder.mutation<{ data: Product }, Partial<Product>>({
      query: (product) => ({
        url: '/products',
        method: 'POST',
        body: product,
      }),
      invalidatesTags: ['Product'],
    }),
    
    // Update product
    updateProduct: builder.mutation<{ data: Product }, { id: number; data: Partial<Product> }>({
      query: ({ id, data }) => ({
        url: `/products/${id}`,
        method: 'PUT',
        body: data,
      }),
      invalidatesTags: (result, error, { id }) => [{ type: 'Product', id }, 'Product'],
    }),
    
    // Delete product
    deleteProduct: builder.mutation<void, number>({
      query: (id) => ({
        url: `/products/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['Product'],
    }),
  }),
});

export const {
  useGetProductsQuery,
  useGetProductQuery,
  useCreateProductMutation,
  useUpdateProductMutation,
  useDeleteProductMutation,
} = productsApi;
```

## Component Usage Example

```typescript
import React from 'react';
import { Table, Button, Space, message } from 'antd';
import { useGetProductsQuery, useDeleteProductMutation } from '../services/products';

const ProductList: React.FC = () => {
  const [page, setPage] = React.useState(1);
  const { data, isLoading, error } = useGetProductsQuery({ page, perPage: 20 });
  const [deleteProduct] = useDeleteProductMutation();

  const handleDelete = async (id: number) => {
    try {
      await deleteProduct(id).unwrap();
      message.success('Product deleted successfully');
    } catch (error: any) {
      message.error(error?.data?.message || 'Failed to delete product');
    }
  };

  if (isLoading) return <Spin />;
  if (error) return <Alert message="Error loading products" type="error" />;

  return (
    <Table
      dataSource={data?.data}
      rowKey="id"
      pagination={{
        current: page,
        total: data?.meta?.total,
        pageSize: data?.meta?.per_page,
        onChange: setPage,
      }}
      columns={[
        { title: 'Name', dataIndex: 'name', key: 'name' },
        { title: 'SKU', dataIndex: 'sku', key: 'sku' },
        { title: 'Price', dataIndex: 'price', key: 'price' },
        {
          title: 'Actions',
          key: 'actions',
          render: (_, record) => (
            <Space>
              <Button onClick={() => handleDelete(record.id)}>Delete</Button>
            </Space>
          ),
        },
      ]}
    />
  );
};
```

## Resources

- **API Reference**: See `docs/API-REFERENCE.md` for complete endpoint documentation
- **Interactive Docs**: http://localhost:8000/docs (Scribe documentation)
- **Postman Collection**: `platform/backend/storage/app/private/scribe/collection.json`
- **OpenAPI Spec**: `platform/backend/storage/app/private/scribe/openapi.yaml`

## Common Issues

### 401 Unauthorized
- Check if token is valid: `localStorage.getItem('auth_token')`
- Verify token is being sent in headers
- Check if token is expired (logout and login again)

### 403 Tenant Isolation Error
- Ensure `X-Store-ID` header is set
- Verify store ID matches authenticated user's stores
- Check if user has access to this store

### 422 Validation Error
- Check request body matches API expectations
- Review error.data.errors for field-specific messages
- Ensure required fields are provided

### CORS Issues
- Backend must have CORS enabled for frontend origin
- Check `config/cors.php` in Laravel backend

## Best Practices

1. **Always use RTK Query** for API calls (not manual fetch/axios)
2. **Type everything** - Create TypeScript interfaces for all API responses
3. **Use invalidatesTags** for cache invalidation after mutations
4. **Handle errors consistently** - Use try/catch with message.error()
5. **Show loading states** - Check isLoading from useQuery
6. **Implement optimistic updates** for better UX
7. **Use query params** for filtering/searching (not separate endpoints)

## When to Use This Skill

- ✅ Creating new API service files
- ✅ Implementing API endpoints in React components
- ✅ Setting up authentication flows
- ✅ Debugging API integration issues
- ✅ Adding new endpoints to existing services
- ✅ Implementing pagination, search, filters
- ✅ Error handling for API calls

## Related Documentation

- `.github/copilot-instructions.md` - General coding guidelines
- `docs/API-REFERENCE.md` - Complete API endpoint reference
- `platform/admin-panel/README.md` - Admin panel setup
