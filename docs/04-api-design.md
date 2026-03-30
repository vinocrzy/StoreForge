# API Design Specification

## Overview

RESTful API design for the e-commerce platform, supporting both admin panel and storefront applications.

## Base URL

```
Production:  https://api.yourplatform.com/v1
Development: http://localhost:8000/api/v1
```

## Authentication

### Admin API
**Method**: Laravel Sanctum (Token-based)

**Login Request**:
```http
POST /api/v1/admin/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

**Response**:
```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "role": "admin",
      "store_id": 1
    },
    "token": "1|abc123..."
  }
}
```

**Authenticated Requests**:
```http
GET /api/v1/admin/products
Authorization: Bearer {token}
X-Store-ID: 1
```

### Storefront API
**Method**: Session-based or Guest tokens

## API Versioning

- Version in URL: `/api/v1/`, `/api/v2/`
- Breaking changes require new version
- Maintain backward compatibility for at least 6 months

## Response Format

### Success Response

```json
{
  "data": {
    "id": 1,
    "name": "Product Name",
    "price": 29.99
  },
  "meta": {
    "timestamp": "2026-03-30T10:00:00Z"
  }
}
```

### Collection Response with Pagination

```json
{
  "data": [
    {
      "id": 1,
      "name": "Product 1"
    },
    {
      "id": 2,
      "name": "Product 2"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "/api/v1/products?page=1",
    "last": "/api/v1/products?page=10",
    "prev": null,
    "next": "/api/v1/products?page=2"
  }
}
```

### Error Response

```json
{
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_ERROR",
    "status": 422,
    "errors": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  },
  "meta": {
    "timestamp": "2026-03-30T10:00:00Z"
  }
}
```

## HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET, PUT, PATCH |
| 201 | Created | Successful POST |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

## Admin API Endpoints

### Authentication

#### Login
```http
POST /api/v1/admin/auth/login
```

**Request**:
```json
{
  "email": "admin@store.com",
  "password": "password"
}
```

**Response** (200):
```json
{
  "data": {
    "user": {...},
    "token": "1|abc...",
    "token_type": "Bearer"
  }
}
```

#### Logout
```http
POST /api/v1/admin/auth/logout
Authorization: Bearer {token}
```

**Response** (204): No content

#### Get Current User
```http
GET /api/v1/admin/auth/user
Authorization: Bearer {token}
```

### Products

#### List Products
```http
GET /api/v1/admin/products
```

**Query Parameters**:
- `page` (int): Page number
- `per_page` (int): Items per page (default: 15, max: 100)
- `search` (string): Search in name, SKU, description
- `status` (string): Filter by status (draft, active, archived)
- `category_id` (int): Filter by category
- `sort` (string): Sort field (name, price, created_at)
- `order` (string): Sort order (asc, desc)
- `include` (string): Include relationships (categories, variants, images)

**Example**:
```
GET /api/v1/admin/products?page=1&per_page=20&status=active&include=categories,images&sort=created_at&order=desc
```

**Response** (200):
```json
{
  "data": [
    {
      "id": 1,
      "store_id": 1,
      "name": "Premium T-Shirt",
      "slug": "premium-t-shirt",
      "sku": "TSHIRT-001",
      "price": 29.99,
      "compare_at_price": 39.99,
      "status": "active",
      "featured": true,
      "categories": [
        {
          "id": 1,
          "name": "Apparel",
          "slug": "apparel"
        }
      ],
      "images": [
        {
          "id": 1,
          "url": "https://cdn.example.com/product-1.jpg",
          "is_primary": true
        }
      ],
      "inventory": {
        "quantity": 100,
        "available_quantity": 95
      },
      "created_at": "2026-03-15T10:00:00Z",
      "updated_at": "2026-03-30T10:00:00Z"
    }
  ],
  "meta": {...},
  "links": {...}
}
```

#### Get Single Product
```http
GET /api/v1/admin/products/{id}
```

**Query Parameters**:
- `include` (string): Include relationships

**Response** (200): Single product object

#### Create Product
```http
POST /api/v1/admin/products
Content-Type: application/json
```

**Request**:
```json
{
  "name": "Premium T-Shirt",
  "slug": "premium-t-shirt",
  "sku": "TSHIRT-001",
  "short_description": "Comfortable cotton t-shirt",
  "description": "Long detailed description...",
  "price": 29.99,
  "compare_at_price": 39.99,
  "cost_per_item": 15.00,
  "type": "simple",
  "status": "active",
  "featured": true,
  "is_taxable": true,
  "requires_shipping": true,
  "track_inventory": true,
  "weight": 0.5,
  "weight_unit": "kg",
  "meta_title": "Premium T-Shirt - Best Quality",
  "meta_description": "Buy premium quality t-shirts...",
  "category_ids": [1, 2],
  "images": [
    {
      "url": "https://cdn.example.com/image1.jpg",
      "alt_text": "Front view",
      "position": 0,
      "is_primary": true
    }
  ],
  "attributes": [
    {
      "attribute_id": 1,
      "value": "Cotton"
    }
  ],
  "initial_stock": 100
}
```

**Response** (201):
```json
{
  "data": {
    "id": 1,
    ...
  },
  "meta": {
    "message": "Product created successfully"
  }
}
```

#### Update Product
```http
PATCH /api/v1/admin/products/{id}
Content-Type: application/json
```

**Request**: Same as create (partial updates allowed)

**Response** (200): Updated product object

#### Delete Product
```http
DELETE /api/v1/admin/products/{id}
```

**Response** (204): No content

#### Bulk Import Products
```http
POST /api/v1/admin/products/bulk-import
Content-Type: multipart/form-data
```

**Request**:
```
file: products.csv
```

**Response** (202):
```json
{
  "data": {
    "job_id": "uuid",
    "status": "processing",
    "message": "Import job queued"
  }
}
```

### Categories

#### List Categories
```http
GET /api/v1/admin/categories
```

**Query Parameters**:
- `parent_id` (int): Filter by parent
- `flat` (bool): Flat list or tree structure

**Response** (200):
```json
{
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "parent_id": null,
      "image_url": "...",
      "is_active": true,
      "children": [
        {
          "id": 2,
          "name": "Laptops",
          "slug": "laptops",
          "parent_id": 1
        }
      ]
    }
  ]
}
```

#### Create Category
```http
POST /api/v1/admin/categories
```

**Request**:
```json
{
  "name": "Electronics",
  "slug": "electronics",
  "parent_id": null,
  "description": "Electronic products",
  "image_url": "...",
  "meta_title": "Electronics",
  "meta_description": "...",
  "is_active": true
}
```

### Inventory

#### Get Inventory List
```http
GET /api/v1/admin/inventory
```

**Query Parameters**:
- `low_stock` (bool): Only show low stock items
- `product_id` (int): Filter by product
- `warehouse_id` (int): Filter by warehouse

**Response** (200):
```json
{
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "product_name": "Premium T-Shirt",
      "sku": "TSHIRT-001",
      "variant_id": null,
      "warehouse_id": 1,
      "warehouse_name": "Main Warehouse",
      "quantity": 100,
      "reserved_quantity": 5,
      "available_quantity": 95,
      "low_stock_threshold": 10,
      "is_low_stock": false,
      "updated_at": "2026-03-30T10:00:00Z"
    }
  ]
}
```

#### Adjust Inventory
```http
POST /api/v1/admin/inventory/{product_id}/adjust
```

**Request**:
```json
{
  "variant_id": null,
  "warehouse_id": 1,
  "quantity": 50,
  "type": "adjustment",
  "notes": "Received new stock"
}
```

**Response** (200):
```json
{
  "data": {
    "id": 1,
    "quantity": 150,
    "available_quantity": 145
  }
}
```

#### Get Low Stock Alerts
```http
GET /api/v1/admin/inventory/alerts
```

**Response** (200):
```json
{
  "data": [
    {
      "product_id": 5,
      "product_name": "Blue Jeans",
      "sku": "JEANS-BLUE",
      "available_quantity": 3,
      "low_stock_threshold": 10
    }
  ]
}
```

### Promotions

#### List Promotions
```http
GET /api/v1/admin/promotions
```

**Query Parameters**:
- `status` (string): Filter by status
- `type` (string): Filter by type

**Response** (200):
```json
{
  "data": [
    {
      "id": 1,
      "name": "Summer Sale",
      "type": "percentage",
      "value": 20.00,
      "status": "active",
      "usage_limit": 1000,
      "used_count": 234,
      "starts_at": "2026-06-01T00:00:00Z",
      "ends_at": "2026-08-31T23:59:59Z"
    }
  ]
}
```

#### Create Promotion
```http
POST /api/v1/admin/promotions
```

**Request**:
```json
{
  "name": "Summer Sale 2026",
  "description": "20% off all summer items",
  "type": "percentage",
  "value": 20.00,
  "status": "scheduled",
  "priority": 1,
  "is_stackable": false,
  "usage_limit": 1000,
  "usage_limit_per_customer": 1,
  "minimum_purchase_amount": 50.00,
  "maximum_discount_amount": 100.00,
  "applies_to": "specific_categories",
  "applies_to_ids": [1, 2, 3],
  "starts_at": "2026-06-01T00:00:00Z",
  "ends_at": "2026-08-31T23:59:59Z"
}
```

**Response** (201): Created promotion object

#### Activate/Deactivate Promotion
```http
POST /api/v1/admin/promotions/{id}/activate
POST /api/v1/admin/promotions/{id}/deactivate
```

**Response** (200):
```json
{
  "data": {
    "id": 1,
    "status": "active"
  }
}
```

### Coupons

#### List Coupons
```http
GET /api/v1/admin/coupons
```

#### Create Coupon
```http
POST /api/v1/admin/coupons
```

**Request**:
```json
{
  "code": "SAVE20",
  "type": "percentage",
  "value": 20.00,
  "status": "active",
  "usage_limit": 100,
  "usage_limit_per_customer": 1,
  "minimum_purchase_amount": 50.00,
  "maximum_discount_amount": 50.00,
  "applies_to": "all",
  "starts_at": "2026-04-01T00:00:00Z",
  "expires_at": "2026-04-30T23:59:59Z"
}
```

#### Get Coupon Usage
```http
GET /api/v1/admin/coupons/{id}/usage
```

**Response** (200):
```json
{
  "data": {
    "coupon_id": 1,
    "code": "SAVE20",
    "total_uses": 45,
    "usage_limit": 100,
    "usages": [
      {
        "order_id": 100,
        "customer_email": "customer@example.com",
        "discount_amount": 10.00,
        "used_at": "2026-04-15T10:00:00Z"
      }
    ]
  }
}
```

### Orders

#### List Orders
```http
GET /api/v1/admin/orders
```

**Query Parameters**:
- `status` (string): Filter by order status
- `payment_status` (string): Filter by payment status
- `customer_id` (int): Filter by customer
- `date_from` (date): Orders from date
- `date_to` (date): Orders to date
- `search` (string): Search by order number, customer email

**Response** (200):
```json
{
  "data": [
    {
      "id": 1,
      "order_number": "ORD-2026-00001",
      "status": "confirmed",
      "payment_status": "paid",
      "customer": {
        "id": 1,
        "email": "customer@example.com",
        "first_name": "John",
        "last_name": "Doe"
      },
      "total": 129.99,
      "items_count": 3,
      "placed_at": "2026-03-30T10:00:00Z"
    }
  ]
}
```

#### Get Single Order
```http
GET /api/v1/admin/orders/{id}
```

**Response** (200):
```json
{
  "data": {
    "id": 1,
    "order_number": "ORD-2026-00001",
    "status": "confirmed",
    "payment_status": "paid",
    "customer": {...},
    "items": [
      {
        "id": 1,
        "product_name": "Premium T-Shirt",
        "sku": "TSHIRT-001",
        "quantity": 2,
        "price": 29.99,
        "discount_amount": 5.00,
        "tax_amount": 2.50,
        "total": 57.48
      }
    ],
    "billing_address": {...},
    "shipping_address": {...},
    "subtotal": 129.99,
    "discount_amount": 10.00,
    "shipping_amount": 10.00,
    "tax_amount": 12.00,
    "total": 141.99,
    "coupon_code": "SAVE10",
    "payment": {...},
    "timeline": [
      {
        "status": "placed",
        "timestamp": "2026-03-30T10:00:00Z"
      },
      {
        "status": "confirmed",
        "timestamp": "2026-03-30T10:05:00Z"
      }
    ]
  }
}
```

#### Update Order Status
```http
PATCH /api/v1/admin/orders/{id}
```

**Request**:
```json
{
  "status": "processing",
  "admin_note": "Started processing"
}
```

#### Fulfill Order
```http
POST /api/v1/admin/orders/{id}/fulfill
```

**Request**:
```json
{
  "tracking_number": "1Z999AA10123456784",
  "carrier": "UPS",
  "notify_customer": true
}
```

#### Cancel Order
```http
POST /api/v1/admin/orders/{id}/cancel
```

**Request**:
```json
{
  "reason": "Out of stock",
  "refund_payment": true,
  "restock_items": true,
  "notify_customer": true
}
```

#### Refund Order
```http
POST /api/v1/admin/orders/{id}/refund
```

**Request**:
```json
{
  "amount": 141.99,
  "reason": "Customer request",
  "restock_items": true,
  "notify_customer": true
}
```

### Customers

#### List Customers
```http
GET /api/v1/admin/customers
```

**Query Parameters**:
- `search` (string): Search email, name
- `status` (string): Filter by status
- `sort` (string): Sort by field

**Response** (200):
```json
{
  "data": [
    {
      "id": 1,
      "email": "customer@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+1234567890",
      "status": "active",
      "total_orders": 5,
      "total_spent": 549.95,
      "average_order_value": 109.99,
      "last_order_at": "2026-03-30T10:00:00Z",
      "created_at": "2026-01-15T10:00:00Z"
    }
  ]
}
```

#### Get Single Customer
```http
GET /api/v1/admin/customers/{id}
```

**Response** (200): Full customer details with addresses

#### Get Customer Orders
```http
GET /api/v1/admin/customers/{id}/orders
```

**Response** (200): Paginated order list

### Analytics

#### Get Sales Analytics
```http
GET /api/v1/admin/analytics/sales
```

**Query Parameters**:
- `period` (string): today, yesterday, week, month, year, custom
- `date_from` (date): For custom period
- `date_to` (date): For custom period
- `compare_previous` (bool): Compare with previous period

**Response** (200):
```json
{
  "data": {
    "period": {
      "from": "2026-03-01T00:00:00Z",
      "to": "2026-03-31T23:59:59Z"
    },
    "metrics": {
      "total_sales": 45890.50,
      "total_orders": 234,
      "average_order_value": 196.11,
      "total_items_sold": 892
    },
    "comparison": {
      "total_sales": {
        "previous": 38230.00,
        "change": 20.0,
        "direction": "up"
      }
    },
    "by_day": [
      {
        "date": "2026-03-01",
        "sales": 1250.00,
        "orders": 8
      }
    ]
  }
}
```

#### Get Product Performance
```http
GET /api/v1/admin/analytics/products
```

**Query Parameters**:
- `period` (string)
- `sort` (string): revenue, units_sold, views
- `limit` (int): Top N products

**Response** (200):
```json
{
  " data": {
    "top_products": [
      {
        "product_id": 1,
        "product_name": "Premium T-Shirt",
        "units_sold": 145,
        "revenue": 4348.55,
        "views": 2340
      }
    ]
  }
}
```

## Storefront API Endpoints

### Public Product API

#### List Products
```http
GET /api/v1/storefront/products
```

**Query Parameters**:
- `category` (string): Category slug
- `search` (string): Search query
- `price_min` (decimal)
- `price_max` (decimal)
- `sort` (string): price_asc, price_desc, name, newest
- `page` (int)
- `per_page` (int)

**Response** (200): Product list with public fields only

#### Get Single Product
```http
GET /api/v1/storefront/products/{slug}
```

**Response** (200): Full product details

### Cart & Checkout

#### Add to Cart
```http
POST /api/v1/storefront/cart
```

**Request**:
```json
{
  "product_id": 1,
  "variant_id": null,
  "quantity": 2
}
```

#### Initialize Checkout
```http
POST /api/v1/storefront/checkout
```

**Request**:
```json
{
  "cart_items": [...],
  "customer": {...},
  "billing_address": {...},
  "shipping_address": {...},
  "coupon_code": "SAVE10",
  "payment_method": "stripe"
}
```

**Response** (200):
```json
{
  "data": {
    "order_id": 1,
    "payment_intent_client_secret": "pi_xxx",
    "total": 141.99
  }
}
```

## Rate Limiting

- **Admin API**: 120 requests per minute per user
- **Storefront API**: 60 requests per minute per IP
- **Public endpoints**: 30 requests per minute per IP

**Rate Limit Headers**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1617123456
```

## Webhooks

Outgoing webhooks for events:

- `order.created`
- `order.updated`
- `order.fulfilled`
- `order.cancelled`
- `payment.completed`
- `payment.failed`
- `inventory.low_stock`

## Next Steps

1. Review [Admin Panel Architecture](05-admin-panel-architecture.md)
2. Review [Storefront Architecture](06-storefront-architecture.md)
3. Implement API in Laravel
