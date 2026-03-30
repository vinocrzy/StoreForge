# Admin Panel Architecture - React

## Overview

The admin panel is a Single Page Application (SPA) built with React, TypeScript, and modern tooling. It provides a comprehensive dashboard for managing e-commerce operations.

## Technology Stack

### Core
- **React** 18.2+
- **TypeScript** 5.0+
- **Vite** 5.0+ (Build tool)
- **React Router** 6.x (Routing)

### State Management
- **Redux Toolkit** 2.0+ (Global state)
- **RTK Query** (API calls & caching)
- **React Query** (Alternative for server state)

### UI Framework
**Option A: Ant Design** (Recommended)
- Complete component library
- Professional admin themes
- Built-in form validation
- Table with advanced features

**Option B: Material-UI (MUI)**
- Modern design
- Extensive component library
- Good TypeScript support

### Forms & Validation
- **React Hook Form** 7.x
- **Zod** / **Yup** (Schema validation)

### Data Visualization
- **Recharts** / **Apache ECharts**
- **React-chartjs-2**

### Additional Libraries
- **Axios** (HTTP client)
- **date-fns** / **dayjs** (Date manipulation)
- **react-dropzone** (File uploads)
- **react-beautiful-dnd** (Drag & drop)
- **socket.io-client** (Real-time updates)

## Project Structure

```
admin-panel/
├── public/
│   ├── favicon.ico
│   └── assets/
├── src/
│   ├── api/
│   │   ├── axios.ts
│   │   ├── endpoints.ts
│   │   └── services/
│   │       ├── authService.ts
│   │       ├── productService.ts
│   │       ├── orderService.ts
│   │       └── ...
│   ├── assets/
│   │   ├── images/
│   │   ├── icons/
│   │   └── styles/
│   ├── components/
│   │   ├── common/
│   │   │   ├── Button/
│   │   │   ├── Input/
│   │   │   ├── Modal/
│   │   │   ├── Table/
│   │   │   └── ...
│   │   ├── layout/
│   │   │   ├── Header/
│   │   │   ├── Sidebar/
│   │   │   ├── Footer/
│   │   │   └── MainLayout.tsx
│   │   └── features/
│   │       ├── products/
│   │       ├── orders/
│   │       ├── customers/
│   │       └── ...
│   ├── config/
│   │   ├── constants.ts
│   │   ├── navigation.ts
│   │   └── theme.ts
│   ├── features/
│   │   ├── auth/
│   │   │   ├── authSlice.ts
│   │   │   ├── Login.tsx
│   │   │   └── authApi.ts
│   │   ├── products/
│   │   │   ├── productsSlice.ts
│   │   │   ├── ProductList.tsx
│   │   │   ├── ProductForm.tsx
│   │   │   ├── ProductDetail.tsx
│   │   │   └── productsApi.ts
│   │   ├── orders/
│   │   ├── inventory/
│   │   ├── promotions/
│   │   ├── customers/
│   │   └── analytics/
│   ├── hooks/
│   │   ├── useAuth.ts
│   │   ├── useDebounce.ts
│   │   ├── usePermission.ts
│   │   └── ...
│   ├── routes/
│   │   ├── index.tsx
│   │   ├── PrivateRoute.tsx
│   │   └── routeConfig.ts
│   ├── store/
│   │   ├── index.ts
│   │   ├── rootReducer.ts
│   │   └── middleware.ts
│   ├── types/
│   │   ├── api.types.ts
│   │   ├── product.types.ts
│   │   ├── order.types.ts
│   │   └── ...
│   ├── utils/
│   │   ├── formatters.ts
│   │   ├── validators.ts
│   │   ├── helpers.ts
│   │   └── constants.ts
│   ├── App.tsx
│   ├── main.tsx
│   └── vite-env.d.ts
├── .env.example
├── .eslintrc.cjs
├── .prettierrc
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.ts
└── README.md
```

## Key Features & Pages

### 1. Dashboard (Home)
**Route**: `/`

**Components**:
- Sales summary cards (today, week, month)
- Recent orders table
- Top selling products
- Revenue chart
- Inventory alerts
- Quick actions

### 2. Products Management
**Routes**:
- `/products` - Product list
- `/products/new` - Create product
- `/products/:id/edit` - Edit product
- `/products/:id` - Product details

**Features**:
- Advanced filtering & search
- Bulk actions (delete, status change, export)
- Product variants management
- Image upload & management
- Category assignment
- Attribute management
- Stock tracking
- SEO metadata

### 3. Categories Management
**Routes**:
- `/categories` - Category list/tree
- `/categories/new` - Create category
- `/categories/:id/edit` - Edit category

**Features**:
- Hierarchical tree view
- Drag & drop reordering
- Bulk operations
- Image upload

### 4. Inventory Management
**Routes**:
- `/inventory` - Inventory list
- `/inventory/adjustments` - Stock adjustments history
- `/inventory/alerts` - Low stock alerts

**Features**:
- Real-time stock levels
- Quick stock adjustment
- Multi-warehouse support
- Stock movement history
- Low stock notifications
- Export reports

### 5. Orders Management
**Routes**:
- `/orders` - Order list
- `/orders/:id` - Order details
- `/orders/:id/edit` - Edit order

**Features**:
- Order status management
- Order filtering & search
- Print invoice
- Send tracking info
- Process refunds
- Order timeline
- Customer details
- Payment information

### 6. Promotions & Discounts
**Routes**:
- `/promotions` - Promotions list
- `/promotions/new` - Create promotion
- `/promotions/:id/edit` - Edit promotion

**Features**:
- Create percentage/fixed discounts
- Schedule promotions
- Set usage limits
- Target specific products/categories/customers
- Track promotion performance

### 7. Coupons Management
**Routes**:
- `/coupons` - Coupon list
- `/coupons/new` - Create coupon
- `/coupons/:id` - Coupon details & usage

**Features**:
- Generate unique codes
- Bulk coupon creation
- Usage tracking
- Expiry management
- Customer restrictions

### 8. Offers Management
**Routes**:
- `/offers` - Offer list
- `/offers/new` - Create offer

**Features**:
- Buy X Get Y offers
- Bundle offers
- Quantity discounts
- Tiered pricing

### 9. Customers Management
**Routes**:
- `/customers` - Customer list
- `/customers/:id` - Customer details
- `/customers/:id/orders` - Customer orders

**Features**:
- Customer profiles
- Order history
- Lifetime value tracking
- Customer segmentation
- Export customer data

### 10. Analytics & Reports
**Routes**:
- `/analytics/sales` - Sales analytics
- `/analytics/products` - Product performance
- `/analytics/customers` - Customer insights
- `/reports` - Report builder

**Features**:
- Interactive charts
- Date range selection
- Export to CSV/Excel
- Period comparison
- Real-time updates

### 11. Settings
**Routes**:
- `/settings/store` - Store settings
- `/settings/users` - User management
- `/settings/roles` - Role & permissions
- `/settings/payment` - Payment gateways
- `/settings/shipping` - Shipping methods
- `/settings/taxes` - Tax configuration

## State Management Architecture

### Redux Store Structure

```typescript
{
  auth: {
    user: User | null,
    token: string | null,
    isAuthenticated: boolean,
    loading: boolean
  },
  products: {
    list: Product[],
    selectedProduct: Product | null,
    filters: ProductFilters,
    pagination: PaginationMeta,
    loading: boolean
  },
  orders: {
    list: Order[],
    selectedOrder: Order | null,
    filters: OrderFilters,
    pagination: PaginationMeta,
    loading: boolean
  },
  inventory: {
    items: InventoryItem[],
    alerts: LowStockAlert[],
    loading: boolean
  },
  ui: {
    sidebarCollapsed: boolean,
    theme: 'light' | 'dark',
    notifications: Notification[]
  }
}
```

### API Service Example (RTK Query)

```typescript
// src/api/services/productApi.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { Product, ProductListResponse, CreateProductRequest } from '@/types';

export const productApi = createApi({
  reducerPath: 'productApi',
  baseQuery: fetchBaseQuery({
    baseUrl: import.meta.env.VITE_API_URL,
    prepareHeaders: (headers, { getState }) => {
      const token = (getState() as RootState).auth.token;
      if (token) {
        headers.set('Authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['Product'],
  endpoints: (builder) => ({
    getProducts: builder.query<ProductListResponse, ProductQueryParams>({
      query: (params) => ({
        url: '/admin/products',
        params,
      }),
      providesTags: ['Product'],
    }),
    getProduct: builder.query<Product, number>({
      query: (id) => `/admin/products/${id}`,
      providesTags: (result, error, id) => [{ type: 'Product', id }],
    }),
    createProduct: builder.mutation<Product, CreateProductRequest>({
      query: (body) => ({
        url: '/admin/products',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['Product'],
    }),
    updateProduct: builder.mutation<Product, { id: number; data: Partial<Product> }>({
      query: ({ id, data }) => ({
        url: `/admin/products/${id}`,
        method: 'PATCH',
        body: data,
      }),
      invalidatesTags: (result, error, { id }) => [{ type: 'Product', id }],
    }),
    deleteProduct: builder.mutation<void, number>({
      query: (id) => ({
        url: `/admin/products/${id}`,
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
} = productApi;
```

## Component Examples

### Product List Component

```typescript
// src/features/products/ProductList.tsx
import { useState } from 'react';
import { Table, Button, Space, Tag, Image, Input } from 'antd';
import { useGetProductsQuery, useDeleteProductMutation } from '@/api/services/productApi';
import { Link } from 'react-router-dom';
import { formatCurrency } from '@/utils/formatters';

export const ProductList = () => {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [filters, setFilters] = useState({});

  const { data, isLoading, error } = useGetProductsQuery({
    page,
    per_page: 20,
    search,
    ...filters,
  });

  const [deleteProduct] = useDeleteProductMutation();

  const columns = [
    {
      title: 'Image',
      dataIndex: 'images',
      key: 'image',
      width: 80,
      render: (images: any[]) => (
        <Image
          src={images[0]?.url || '/placeholder.png'}
          alt="Product"
          width={50}
          height={50}
        />
      ),
    },
    {
      title: 'Name',
      dataIndex: 'name',
      key: 'name',
      render: (text: string, record: any) => (
        <Link to={`/products/${record.id}`}>{text}</Link>
      ),
    },
    {
      title: 'SKU',
      dataIndex: 'sku',
      key: 'sku',
    },
    {
      title: 'Price',
      dataIndex: 'price',
      key: 'price',
      render: (price: number) => formatCurrency(price),
    },
    {
      title: 'Stock',
      dataIndex: 'inventory',
      key: 'stock',
      render: (inventory: any) => inventory?.available_quantity || 0,
    },
    {
      title: 'Status',
      dataIndex: 'status',
      key: 'status',
      render: (status: string) => {
        const color = {
          active: 'green',
          draft: 'orange',
          archived: 'red',
        }[status];
        return <Tag color={color}>{status.toUpperCase()}</Tag>;
      },
    },
    {
      title: 'Actions',
      key: 'actions',
      render: (_: any, record: any) => (
        <Space>
          <Button size="small" type="link">
            <Link to={`/products/${record.id}/edit`}>Edit</Link>
          </Button>
          <Button
            size="small"
            type="link"
            danger
            onClick={() => handleDelete(record.id)}
          >
            Delete
          </Button>
        </Space>
      ),
    },
  ];

  const handleDelete = async (id: number) => {
    if (confirm('Are you sure you want to delete this product?')) {
      await deleteProduct(id);
    }
  };

  return (
    <div>
      <Space style={{ marginBottom: 16 }}>
        <Input.Search
          placeholder="Search products..."
          onSearch={setSearch}
          style={{ width: 300 }}
        />
        <Link to="/products/new">
          <Button type="primary">Add Product</Button>
        </Link>
      </Space>

      <Table
        columns={columns}
        dataSource={data?.data}
        loading={isLoading}
        rowKey="id"
        pagination={{
          current: page,
          pageSize: 20,
          total: data?.meta.total,
          onChange: setPage,
        }}
      />
    </div>
  );
};
```

### Product Form Component

```typescript
// src/features/products/ProductForm.tsx
import { Form, Input, InputNumber, Select, Button, Upload, Switch } from 'antd';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useCreateProductMutation, useUpdateProductMutation } from '@/api/services/productApi';

const productSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  slug: z.string().min(1, 'Slug is required'),
  sku: z.string().min(1, 'SKU is required'),
  price: z.number().positive('Price must be positive'),
  description: z.string().optional(),
  status: z.enum(['draft', 'active', 'archived']),
  featured: z.boolean(),
  track_inventory: z.boolean(),
});

type ProductFormData = z.infer<typeof productSchema>;

interface ProductFormProps {
  initialData?: ProductFormData;
  onSuccess?: () => void;
}

export const ProductForm = ({ initialData, onSuccess }: ProductFormProps) => {
  const {
    control,
    handleSubmit,
    formState: { errors },
  } = useForm<ProductFormData>({
    resolver: zodResolver(productSchema),
    defaultValues: initialData,
  });

  const [createProduct, { isLoading: isCreating }] = useCreateProductMutation();
  const [updateProduct, { isLoading: isUpdating }] = useUpdateProductMutation();

  const onSubmit = async (data: ProductFormData) => {
    try {
      if (initialData) {
        await updateProduct({ id: initialData.id, data }).unwrap();
      } else {
        await createProduct(data).unwrap();
      }
      onSuccess?.();
    } catch (error) {
      console.error('Failed to save product:', error);
    }
  };

  return (
    <Form layout="vertical" onFinish={handleSubmit(onSubmit)}>
      <Form.Item
        label="Product Name"
        validateStatus={errors.name ? 'error' : ''}
        help={errors.name?.message}
      >
        <Controller
          name="name"
          control={control}
          render={({ field }) => <Input {...field} placeholder="Enter product name" />}
        />
      </Form.Item>

      <Form.Item
        label="SKU"
        validateStatus={errors.sku ? 'error' : ''}
        help={errors.sku?.message}
      >
        <Controller
          name="sku"
          control={control}
          render={({ field }) => <Input {...field} placeholder="PROD-001" />}
        />
      </Form.Item>

      <Form.Item
        label="Price"
        validateStatus={errors.price ? 'error' : ''}
        help={errors.price?.message}
      >
        <Controller
          name="price"
          control={control}
          render={({ field }) => (
            <InputNumber
              {...field}
              style={{ width: '100%' }}
              min={0}
              step={0.01}
              prefix="$"
            />
          )}
        />
      </Form.Item>

      <Form.Item label="Status">
        <Controller
          name="status"
          control={control}
          render={({ field }) => (
            <Select {...field}>
              <Select.Option value="draft">Draft</Select.Option>
              <Select.Option value="active">Active</Select.Option>
              <Select.Option value="archived">Archived</Select.Option>
            </Select>
          )}
        />
      </Form.Item>

      <Form.Item label="Featured">
        <Controller
          name="featured"
          control={control}
          render={({ field }) => (
            <Switch checked={field.value} onChange={field.onChange} />
          )}
        />
      </Form.Item>

      <Form.Item>
        <Button
          type="primary"
          htmlType="submit"
          loading={isCreating || isUpdating}
        >
          {initialData ? 'Update Product' : 'Create Product'}
        </Button>
      </Form.Item>
    </Form>
  );
};
```

## Routing Configuration

```typescript
// src/routes/index.tsx
import { createBrowserRouter } from 'react-router-dom';
import { MainLayout } from '@/components/layout/MainLayout';
import { PrivateRoute } from './PrivateRoute';
import { Login } from '@/features/auth/Login';
import { Dashboard } from '@/features/dashboard/Dashboard';
import { ProductList } from '@/features/products/ProductList';
import { ProductForm } from '@/features/products/ProductForm';
// ... other imports

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <Login />,
  },
  {
    path: '/',
    element: <PrivateRoute><MainLayout /></PrivateRoute>,
    children: [
      {
        index: true,
        element: <Dashboard />,
      },
      {
        path: 'products',
        children: [
          { index: true, element: <ProductList /> },
          { path: 'new', element: <ProductForm /> },
          { path: ':id/edit', element: <ProductForm /> },
          { path: ':id', element: <ProductDetail /> },
        ],
      },
      {
        path: 'orders',
        children: [
          { index: true, element: <OrderList /> },
          { path: ':id', element: <OrderDetail /> },
        ],
      },
      // ... other routes
    ],
  },
]);
```

## Environment Configuration

```env
# .env.example
VITE_API_URL=http://localhost:8000/api/v1
VITE_APP_NAME=E-Commerce Admin
VITE_UPLOAD_MAX_SIZE=5242880
VITE_ENABLE_ANALYTICS=true
```

## Build & Deployment

### Development
```bash
npm run dev
```

### Production Build
```bash
npm run build
```

### Build Output
```
dist/
├── index.html
├── assets/
│   ├── index-[hash].js
│   ├── index-[hash].css
│   └── ...
```

### Deployment Options
- **Static Hosting**: Vercel, Netlify, AWS S3 + CloudFront
- **Docker**: Nginx container serving static files
- **CDN**: CloudFlare Pages

## Performance Optimization

1. **Code Splitting**: Route-based lazy loading
2. **Image Optimization**: WebP format, lazy loading
3. **Caching**: RTK Query automatic caching
4. **Debouncing**: Search inputs, filters
5. **Virtual Scrolling**: Large lists/tables
6. **Memoization**: React.memo, useMemo, useCallback

## Security Considerations

1. **Token Management**: Secure token storage (httpOnly cookies or secure localStorage)
2. **XSS Protection**: Sanitize user inputs
3. **CSRF Protection**: CSRF tokens for state-changing operations
4. **Role-Based Access**: Route and component-level permissions
5. **API Security**: HTTPS only, request validation

## Testing Strategy

```
tests/
├── unit/
│   ├── components/
│   ├── utils/
│   └── hooks/
├── integration/
│   └── features/
└── e2e/
    └── flows/
```

- **Unit Tests**: Jest + React Testing Library
- **Integration Tests**: Testing feature workflows
- **E2E Tests**: Playwright / Cypress

## Next Steps

1. Review [Storefront Architecture](06-storefront-architecture.md)
2. Review [Development Roadmap](10-development-roadmap.md)
3. Set up React project with Vite
