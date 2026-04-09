# E-Commerce Platform API Reference

**Base URL**: `http://localhost:8000/api/v1`  
**Authentication**: Bearer Token via Laravel Sanctum  
**Tenant Header**: `X-Store-ID: {store_id}` (required for tenant-scoped authenticated endpoints)

---

## 📋 Table of Contents

1. [Authentication](#authentication)
2. [Products](#products)
3. [Categories](#categories)
4. [Customers](#customers)
5. [Customer Addresses](#customer-addresses)
6. [Orders](#orders)
7. [Payments](#payments)
8. [Inventory](#inventory)
9. [Warehouses](#warehouses)
10. [Profile](#profile)
11. [Stores (Super Admin)](#-stores-super-admin)
12. [Common Patterns](#-common-patterns)

---

## 🔐 Authentication

### Login
**Phone-first authentication**: Accepts phone (`+12025551234`) or email.

```http
POST /auth/login
Content-Type: application/json

{
  "login": "+12025551234",  // or "admin@example.com"
  "password": "password",
  "device_name": "web-app"  // optional
}
```

**Response 200**:
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "phone": "+12025551234",
    "status": "active"
  },
  "token": "1|abc123...",
  "stores": [
    {
      "id": 1,
      "name": "My Store",
      "slug": "my-store",
      "role": "owner"
    }
  ]
}
```

### Logout
```http
POST /auth/logout
Authorization: Bearer {token}
```

### Get Current User
```http
GET /auth/me
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Revoke All Tokens
```http
POST /auth/revoke-all
Authorization: Bearer {token}
```

---

## 👤 Profile

### Get Profile
```http
GET /profile
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Update Profile
```http
PATCH /profile
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+12025551234"
}
```

### Change Password
```http
PATCH /profile/password
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "current_password": "oldpassword123",
  "password": "newpassword456",
  "password_confirmation": "newpassword456"
}
```

---

## 🛍️ Products

### List Products
```http
GET /products?page=1&per_page=20&search=laptop&status=active&category_id=5
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

**Query Parameters**:
- `page` (int): Page number
- `per_page` (int): Items per page (max 100)
- `search` (string): Search in name, SKU, description
- `status` (string): `active`, `draft`, `archived`
- `category_id` (int): Filter by category
- `featured` (boolean): Filter featured products
- `in_stock` (boolean): Filter by stock availability

**Response 200**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Premium Laptop Pro",
      "slug": "premium-laptop-pro",
      "sku": "LAP-001",
      "description": "High-performance laptop",
      "price": "999.99",
      "compare_at_price": "1299.99",
      "cost_per_item": "700.00",
      "status": "active",
      "featured": true,
      "track_inventory": true,
      "quantity": 50,
      "low_stock_threshold": 10,
      "primary_image_url": "https://...",
      "categories": [],
      "images": [],
      "variants": []
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 90
  }
}
```

### Get Product
```http
GET /products/{id}
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Create Product
```http
POST /products
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "name": "New Product",
  "slug": "new-product",  // auto-generated if omitted
  "sku": "PROD-001",
  "description": "Product description",
  "price": 99.99,
  "compare_at_price": 129.99,
  "cost_per_item": 60.00,
  "status": "active",
  "featured": false,
  "track_inventory": true,
  "quantity": 100,
  "low_stock_threshold": 10,
  "categories": [1, 2, 3],  // category IDs
  "images": [
    {
      "url": "https://...",
      "alt_text": "Product image",
      "position": 1,
      "is_primary": true
    }
  ]
}
```

### Update Product
```http
PUT /products/{id}
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "name": "Updated Product Name",
  "price": 109.99
}
```

### Delete Product
```http
DELETE /products/{id}
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Update Stock
```http
POST /products/{id}/stock
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "operation": "set",  // "set", "increment", "decrement"
  "quantity": 50
}
```

### Export Products CSV
```http
GET /products/export?search=laptop&status=active&stock_status=in_stock
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

Exports filtered products as a CSV file.

---

## 📂 Categories

### List Categories
```http
GET /categories?search=electronics&status=active
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Get Category Tree
```http
GET /categories/tree
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

**Response**: Hierarchical category structure
```json
{
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "parent_id": null,
      "position": 1,
      "children": [
        {
          "id": 2,
          "name": "Laptops",
          "slug": "laptops",
          "parent_id": 1,
          "position": 1
        }
      ]
    }
  ]
}
```

### Create Category
```http
POST /categories
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "name": "New Category",
  "slug": "new-category",  // auto-generated if omitted
  "description": "Category description",
  "parent_id": null,  // or parent category ID
  "status": "active",
  "position": 1,
  "is_featured": false
}
```

### Update Category
```http
PUT /categories/{id}
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Reorder Categories
```http
POST /categories/reorder
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "categories": [
    { "id": 1, "position": 1 },
    { "id": 2, "position": 2 }
  ]
}
```

### Move Category
```http
POST /categories/{id}/move
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "parent_id": 5  // null to move to root
}
```

---

## 👥 Customers

### List Customers
```http
GET /customers?page=1&search=john&status=active
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

**Query Parameters**:
- `search`: Search in name, email, phone
- `status`: `active`, `inactive`
- `verified`: `true`, `false`

**Response 200**:
```json
{
  "data": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "+12025551234",
      "status": "active",
      "email_verified": true,
      "phone_verified": true,
      "addresses_count": 2,
      "orders_count": 5,
      "total_spent": "1250.50"
    }
  ]
}
```

### Get Customer
```http
GET /customers/{id}
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Create Customer
```http
POST /customers
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+12025551234",
  "password": "password123",
  "status": "active"
}
```

### Update Customer Status
```http
POST /customers/{id}/status
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "status": "active"  // or "inactive"
}
```

### Customer Statistics
```http
GET /customers/statistics
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

**Response**:
```json
{
  "data": {
    "total_customers": 45,
    "active_customers": 42,
    "verified_emails": 38,
    "verified_phones": 40,
    "customers_with_orders": 30
  }
}
```

---

## 📍 Customer Addresses

### List Addresses
```http
GET /customers/{customerId}/addresses
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Create Address
```http
POST /customers/{customerId}/addresses
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "address_type": "shipping",  // or "billing"
  "first_name": "John",
  "last_name": "Doe",
  "company": "Acme Inc",
  "address_line1": "123 Main St",
  "address_line2": "Apt 4B",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country": "US",
  "phone": "+12025551234",
  "is_default": false
}
```

### Set Default Address
```http
POST /customers/{customerId}/addresses/{addressId}/default
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

---

## 📦 Orders

### List Orders
```http
GET /orders?status=pending&payment_status=paid&customer_id=5
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

**Query Parameters**:
- `status`: `pending`, `confirmed`, `processing`, `shipped`, `delivered`, `cancelled`, `refunded`
- `payment_status`: `pending`, `paid`, `failed`, `refunded`
- `customer_id`: Filter by customer
- `search`: Search by order number or customer name

**Response 200**:
```json
{
  "data": [
    {
      "id": 1,
      "order_number": "ORD-1-240406-0001",
      "customer_id": 5,
      "status": "confirmed",
      "payment_status": "paid",
      "fulfillment_status": "unfulfilled",
      "subtotal": "150.00",
      "discount_amount": "10.00",
      "shipping_amount": "15.00",
      "tax_amount": "15.50",
      "total": "170.50",
      "currency": "USD",
      "placed_at": "2024-04-06T10:30:00Z",
      "customer": { "id": 5, "first_name": "John", "last_name": "Doe" }
    }
  ]
}
```

### Get Order
```http
GET /orders/{id}
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

**Response**: Order with items and payments
```json
{
  "data": {
    "id": 1,
    "order_number": "ORD-1-240406-0001",
    "customer": {},
    "items": [
      {
        "id": 1,
        "product_id": 10,
        "quantity": 2,
        "price": "99.99",
        "total": "209.98",
        "product_snapshot": {
          "name": "Product Name",
          "sku": "PROD-001"
        }
      }
    ],
    "payments": []
  }
}
```

### Create Order
```http
POST /orders
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "customer_id": 5,
  "items": [
    {
      "product_id": 10,
      "quantity": 2,
      "price": 99.99
    }
  ],
  "payment_method": "bank_transfer",
  "customer_note": "Please gift wrap",
  "shipping_address_id": 3,
  "billing_address_id": 3,
  "coupon_code": "SAVE10"
}
```

### Update Order Status
```http
POST /orders/{id}/status
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "status": "confirmed"
}
```

### Cancel Order
```http
POST /orders/{id}/cancel
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "reason": "Customer requested cancellation"
}
```

### Record Payment
```http
POST /orders/{id}/payment
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "payment_method": "bank_transfer",
  "amount": 170.50,
  "transaction_id": "TXN-123456",
  "payment_notes": "Received via bank transfer"
}
```

### Fulfill Order
```http
POST /orders/{id}/fulfill
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Order Statistics
```http
GET /orders/statistics
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

**Response**:
```json
{
  "data": {
    "total_orders": 45,
    "pending_orders": 5,
    "processing_orders": 8,
    "shipped_orders": 10,
    "delivered_orders": 20,
    "cancelled_orders": 2,
    "total_revenue": "12500.50",
    "pending_payments": "350.00"
  }
}
```

---

## 💰 Payments

Payments are created through the order payment endpoint (see above).
They are automatically linked to orders.

---

## 📊 Inventory

### List Inventory
```http
GET /inventory?warehouse_id=1&product_id=10
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Get Inventory by Product
```http
GET /inventory/product/{productId}
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

**Response**: All warehouse inventory for a product
```json
{
  "data": [
    {
      "id": 1,
      "warehouse_id": 1,
      "product_id": 10,
      "quantity_on_hand": 100,
      "available_quantity": 85,
      "reserved_quantity": 15,
      "warehouse": {
        "id": 1,
        "name": "Main Warehouse"
      }
    }
  ]
}
```

### Adjust Stock
```http
POST /inventory/adjust
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "inventory_id": 1,
  "adjustment_type": "increase",  // or "decrease"
  "quantity": 10,
  "reason": "Restocking"
}
```

### Reserve Stock
```http
POST /inventory/reserve
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "inventory_id": 1,
  "quantity": 5,
  "reference_type": "order",
  "reference_id": 123
}
```

### Release Stock
```http
POST /inventory/release
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "inventory_id": 1,
  "quantity": 5
}
```

### Fulfill Stock
```http
POST /inventory/fulfill
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "inventory_id": 1,
  "quantity": 5
}
```

### Transfer Stock
```http
POST /inventory/transfer
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "from_inventory_id": 1,
  "to_inventory_id": 2,
  "quantity": 10,
  "notes": "Transfer between warehouses"
}
```

### Stock Movements
```http
GET /inventory/movements?inventory_id=1&type=sale
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Stock Alerts
```http
GET /stock-alerts?status=active&alert_type=low_stock
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Resolve Stock Alert
```http
PATCH /stock-alerts/{id}/resolve
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

---

## 🏪 Warehouses

### List Warehouses
```http
GET /warehouses?is_active=1
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Set Default Warehouse
```http
PATCH /warehouses/{id}/set-default
Authorization: Bearer {token}
X-Store-ID: {store_id}
```

### Create Warehouse
```http
POST /warehouses
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "name": "Main Warehouse",
  "code": "MAIN-001",
  "address": "123 Warehouse St",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country": "US",
  "is_active": true
}
```

---

## 🏬 Stores (Super Admin)

These endpoints are for global tenant provisioning and do not require `X-Store-ID`.

### List Stores
```http
GET /stores?search=demo&status=active&page=1&per_page=20
Authorization: Bearer {token}
```

### Create Store
```http
POST /stores
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Honey Bee",
  "slug": "honey-bee",
  "domain": "honey-bee.demo.localhost",
  "status": "active",
  "email": "contact@honeybee.com",
  "phone": "+12025550111",
  "currency": "USD",
  "timezone": "America/New_York",
  "language": "en",
  "admin_name": "Honey Admin",
  "admin_phone": "+12025550112",
  "admin_email": "admin@honeybee.com",
  "admin_password": "SecurePass123"
}
```

### Get Store
```http
GET /stores/{id}
Authorization: Bearer {token}
```

### Update Store Status
```http
PATCH /stores/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "inactive"
}
```

---

## 🔄 Common Patterns

### Pagination
All list endpoints support pagination:
```
?page=1&per_page=20
```

Response includes meta and links:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  },
  "links": {
    "first": "...",
    "last": "...",
    "next": "...",
    "prev": null
  }
}
```

### Error Responses

**422 Validation Error**:
```json
{
  "message": "The given data was invalid",
  "errors": {
    "email": ["The email field is required"],
    "password": ["The password must be at least 8 characters"]
  }
}
```

**401 Unauthorized**:
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden**:
```json
{
  "message": "This action is unauthorized."
}
```

**404 Not Found**:
```json
{
  "message": "Resource not found."
}
```

### Headers Required

Tenant-scoped authenticated requests need:
```
Authorization: Bearer {token}
X-Store-ID: {store_id}
Accept: application/json
Content-Type: application/json
```

Global Super Admin endpoints (for example `/stores`) require only:
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Status Enums

**Order Status**:
- `pending`, `confirmed`, `processing`, `shipped`, `delivered`, `cancelled`, `refunded`

**Payment Status**:
- `pending`, `paid`, `failed`, `refunded`, `partially_refunded`

**Fulfillment Status**:
- `unfulfilled`, `partial`, `fulfilled`

**Product Status**:
- `active`, `draft`, `archived`

**Customer Status**:
- `active`, `inactive`

---

## 📚 Full API Documentation

Interactive API documentation available at:
- **Development**: http://localhost:8000/docs
- **Postman Collection**: `storage/app/private/scribe/collection.json`
- **OpenAPI Spec**: `storage/app/private/scribe/openapi.yaml`

---

## 🎯 Quick Start Examples

### RTK Query Service (React)
```typescript
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const productsApi = createApi({
  reducerPath: 'productsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: 'http://localhost:8000/api/v1',
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('auth_token');
      const storeId = localStorage.getItem('store_id');
      if (token) headers.set('Authorization', `Bearer ${token}`);
      if (storeId) headers.set('X-Store-ID', storeId);
      return headers;
    },
  }),
  endpoints: (builder) => ({
    getProducts: builder.query({
      query: (params) => ({
        url: '/products',
        params,
      }),
    }),
    createProduct: builder.mutation({
      query: (product) => ({
        url: '/products',
        method: 'POST',
        body: product,
      }),
    }),
  }),
});

export const { useGetProductsQuery, useCreateProductMutation } = productsApi;
```

### Usage in Component
```typescript
import { useGetProductsQuery } from '../services/products';

const ProductList: React.FC = () => {
  const { data, isLoading, error } = useGetProductsQuery({ 
    page: 1, 
    status: 'active' 
  });

  if (isLoading) return <Spin />;
  if (error) return <Alert message="Error" />;

  return <Table dataSource={data?.data} />;
};
```

---

**Last Updated**: April 7, 2026  
**API Version**: v1  
**Total Endpoints**: 60
