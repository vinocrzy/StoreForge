---
name: ecommerce-admin-ui
description: 'Build admin panel UI components using TailAdmin design system. Use when: creating new admin pages, designing forms, implementing tables/charts, styling components, or working with the admin panel design system.'
argument-hint: 'Specify component type: "page", "form", "table", "chart", "modal", or "general" for design system guidance'
---

# E-Commerce Admin Panel UI Development

## Purpose

Guide for building consistent, accessible, and performant UI components for the e-commerce admin panel using the TailAdmin design system with React 19 + TypeScript 6 + Tailwind CSS 4.

## When to Use

- Creating new admin panel pages
- Building forms (product creation, order management, etc.)
- Implementing data tables and lists
- Adding charts and data visualizations
- Creating modals, dropdowns, and overlays
- Styling components with Tailwind CSS
- Implementing dark mode support
- Need design system reference

## Design System Reference

**Full Documentation**: `docs/19-admin-panel-design-system.md`

## Technology Stack

```json
{
  "react": "19.2.4",
  "typescript": "6.0.2",
  "tailwindcss": "4.0.8",
  "vite": "8.0.4",
  "react-router": "7.14.0",
  "@reduxjs/toolkit": "2.11.2"
}
```

**Template**: TailAdmin Pro (custom components, no Ant Design)

## Quick Reference

### Color Palette

```javascript
// Use these semantic color classes
'bg-primary'      // #3C50E0 - Primary actions
'bg-success'      // #10B981 - Success states
'bg-warning'      // #FBBF24 - Warnings
'bg-danger'       // #EF4444 - Errors
'text-body'       // #64748B - Body text
'border-stroke'   // #E2E8F0 - Borders
```

### Component Imports

```tsx
// UI Components
import { Button } from '../components/ui/button/Button';
import { Alert } from '../components/ui/alert/Alert';
import { Badge } from '../components/ui/badge/Badge';
import { Dropdown } from '../components/ui/dropdown/Dropdown';
import { Table } from '../components/ui/table';
import { Modal } from '../components/ui/modal';
import { Avatar } from '../components/ui/avatar/Avatar';

// Icons
import { 
  GridIcon, 
  BoxCubeIcon, 
  UserCircleIcon,
  DollarLineIcon,
  ShootingStarIcon,
  ChevronDownIcon 
} from '../icons';

// Charts
import ReactApexChart from 'react-apexcharts';
import { type ApexOptions } from 'apexcharts';
```

## Common Patterns

### 1. Create a New Page

**Template Pattern**:
```tsx
// src/pages/Products/index.tsx
const ProductsPage = () => {
  return (
    <div className="p-6">
      {/* Page Header */}
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
          Products
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Manage your product catalog
        </p>
      </div>
      
      {/* Action Bar */}
      <div className="mb-6 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <input
            type="text"
            placeholder="Search products..."
            className="w-80 rounded-lg border border-stroke bg-white py-2.5 px-4 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
          />
        </div>
        <Button variant="primary" onClick={handleAddProduct}>
          Add Product
        </Button>
      </div>
      
      {/* Content Card */}
      <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark">
        {/* Table or content here */}
      </div>
    </div>
  );
};

export default ProductsPage;
```

**Then add route**:
```tsx
// src/App.tsx
import ProductsPage from "./pages/Products";

<Route path="/products" element={<ProductsPage />} />
```

### 2. Build a Form

**Form with Validation Pattern**:
```tsx
import { useState } from 'react';
import { Button } from '../components/ui/button/Button';
import { Alert } from '../components/ui/alert/Alert';

interface FormData {
  name: string;
  price: number;
  category: string;
  description: string;
}

const ProductForm = () => {
  const [formData, setFormData] = useState<FormData>({
    name: '',
    price: 0,
    category: '',
    description: '',
  });
  const [errors, setErrors] = useState<Partial<Record<keyof FormData, string>>>({});
  const [alert, setAlert] = useState<{type: 'success' | 'error', message: string} | null>(null);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    // Clear error on change
    if (errors[name as keyof FormData]) {
      setErrors(prev => ({ ...prev, [name]: undefined }));
    }
  };

  const validate = (): boolean => {
    const newErrors: Partial<Record<keyof FormData, string>> = {};
    
    if (!formData.name.trim()) newErrors.name = 'Product name is required';
    if (formData.price <= 0) newErrors.price = 'Price must be greater than 0';
    if (!formData.category) newErrors.category = 'Category is required';
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validate()) return;
    
    try {
      // API call here
      await createProduct(formData);
      setAlert({ type: 'success', message: 'Product created successfully!' });
      // Reset form
      setFormData({ name: '', price: 0, category: '', description: '' });
    } catch (error) {
      setAlert({ type: 'error', message: 'Failed to create product' });
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Alert */}
      {alert && (
        <Alert type={alert.type} onClose={() => setAlert(null)}>
          {alert.message}
        </Alert>
      )}
      
      {/* Name Field */}
      <div>
        <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Product Name <span className="text-danger">*</span>
        </label>
        <input
          type="text"
          name="name"
          value={formData.name}
          onChange={handleChange}
          className={`w-full rounded-lg border ${
            errors.name ? 'border-danger' : 'border-stroke dark:border-strokedark'
          } bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none`}
          placeholder="Enter product name"
        />
        {errors.name && (
          <p className="mt-1 text-sm text-danger">{errors.name}</p>
        )}
      </div>

      {/* Price Field */}
      <div>
        <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Price <span className="text-danger">*</span>
        </label>
        <input
          type="number"
          name="price"
          value={formData.price}
          onChange={handleChange}
          className={`w-full rounded-lg border ${
            errors.price ? 'border-danger' : 'border-stroke dark:border-strokedark'
          } bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none`}
          placeholder="0.00"
          step="0.01"
        />
        {errors.price && (
          <p className="mt-1 text-sm text-danger">{errors.price}</p>
        )}
      </div>

      {/* Category Select */}
      <div>
        <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Category <span className="text-danger">*</span>
        </label>
        <select
          name="category"
          value={formData.category}
          onChange={handleChange}
          className={`w-full rounded-lg border ${
            errors.category ? 'border-danger' : 'border-stroke dark:border-strokedark'
          } bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none`}
        >
          <option value="">Select category</option>
          <option value="electronics">Electronics</option>
          <option value="clothing">Clothing</option>
          <option value="food">Food</option>
        </select>
        {errors.category && (
          <p className="mt-1 text-sm text-danger">{errors.category}</p>
        )}
      </div>

      {/* Description Textarea */}
      <div>
        <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Description
        </label>
        <textarea
          name="description"
          value={formData.description}
          onChange={handleChange}
          rows={4}
          className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
          placeholder="Product description..."
        />
      </div>

      {/* Actions */}
      <div className="flex gap-3">
        <Button type="submit" variant="primary">
          Create Product
        </Button>
        <Button type="button" variant="ghost" onClick={() => history.back()}>
          Cancel
        </Button>
      </div>
    </form>
  );
};
```

### 3. Create a Data Table

**Table with Sorting & Pagination**:
```tsx
import { useState, useMemo } from 'react';
import { Table } from '../components/ui/table';
import { Badge } from '../components/ui/badge/Badge';
import { useGetProductsQuery } from '../services/products';

const ProductTable = () => {
  const [page, setPage] = useState(1);
  const [sortBy, setSortBy] = useState<'name' | 'price' | 'stock'>('name');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('asc');

  const { data: products, isLoading } = useGetProductsQuery({ page, perPage: 20 });

  const sortedProducts = useMemo(() => {
    if (!products?.data) return [];
    
    return [...products.data].sort((a, b) => {
      const multiplier = sortOrder === 'asc' ? 1 : -1;
      return a[sortBy] > b[sortBy] ? multiplier : -multiplier;
    });
  }, [products, sortBy, sortOrder]);

  const handleSort = (column: typeof sortBy) => {
    if (sortBy === column) {
      setSortOrder(prev => prev === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(column);
      setSortOrder('asc');
    }
  };

  if (isLoading) {
    return <div className="p-6 text-center">Loading...</div>;
  }

  return (
    <div className="overflow-x-auto">
      <Table>
        <thead>
          <tr className="bg-gray-50 dark:bg-boxdark-2">
            <th className="px-6 py-4 text-left">
              <button
                onClick={() => handleSort('name')}
                className="flex items-center gap-2 font-semibold text-gray-900 dark:text-white hover:text-primary"
              >
                Product Name
                {sortBy === 'name' && (
                  <span>{sortOrder === 'asc' ? '↑' : '↓'}</span>
                )}
              </button>
            </th>
            <th className="px-6 py-4 text-left">
              <button
                onClick={() => handleSort('price')}
                className="flex items-center gap-2 font-semibold text-gray-900 dark:text-white hover:text-primary"
              >
                Price
                {sortBy === 'price' && (
                  <span>{sortOrder === 'asc' ? '↑' : '↓'}</span>
                )}
              </button>
            </th>
            <th className="px-6 py-4 text-left">
              <button
                onClick={() => handleSort('stock')}
                className="flex items-center gap-2 font-semibold text-gray-900 dark:text-white hover:text-primary"
              >
                Stock
                {sortBy === 'stock' && (
                  <span>{sortOrder === 'asc' ? '↑' : '↓'}</span>
                )}
              </button>
            </th>
            <th className="px-6 py-4 text-left font-semibold text-gray-900 dark:text-white">
              Status
            </th>
            <th className="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">
              Actions
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
          {sortedProducts.map((product) => (
            <tr key={product.id} className="hover:bg-gray-50 dark:hover:bg-boxdark-2">
              <td className="px-6 py-4">
                <div className="flex items-center gap-3">
                  <img
                    src={product.image || '/placeholder.png'}
                    alt={product.name}
                    className="h-10 w-10 rounded-lg object-cover"
                  />
                  <span className="font-medium text-gray-900 dark:text-white">
                    {product.name}
                  </span>
                </div>
              </td>
              <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                ${product.price.toFixed(2)}
              </td>
              <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                {product.stock}
              </td>
              <td className="px-6 py-4">
                <Badge variant={product.status === 'active' ? 'success' : 'danger'}>
                  {product.status}
                </Badge>
              </td>
              <td className="px-6 py-4 text-right">
                <div className="flex items-center justify-end gap-2">
                  <button className="text-primary hover:text-primary/80">
                    Edit
                  </button>
                  <button className="text-danger hover:text-danger/80">
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>

      {/* Pagination */}
      <div className="mt-6 flex items-center justify-between">
        <p className="text-sm text-gray-600 dark:text-gray-400">
          Showing {(page - 1) * 20 + 1} to {Math.min(page * 20, products?.meta?.total || 0)} of{' '}
          {products?.meta?.total || 0} products
        </p>
        <div className="flex gap-2">
          <Button
            variant="ghost"
            onClick={() => setPage(prev => Math.max(1, prev - 1))}
            disabled={page === 1}
          >
            Previous
          </Button>
          <Button
            variant="ghost"
            onClick={() => setPage(prev => prev + 1)}
            disabled={page >= (products?.meta?.last_page || 1)}
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  );
};
```

### 4. Add Charts/Data Visualization

**ApexCharts Line Chart**:
```tsx
import ReactApexChart from 'react-apexcharts';
import { type ApexOptions } from 'apexcharts';
import { useMemo } from 'react';

interface SalesChartProps {
  data: { month: string; sales: number }[];
}

const SalesChart: React.FC<SalesChartProps> = ({ data }) => {
  const options: ApexOptions = useMemo(() => ({
    chart: {
      type: 'line',
      height: 350,
      toolbar: { show: false },
      fontFamily: 'inherit',
    },
    colors: ['#3C50E0'],
    stroke: {
      width: 3,
      curve: 'smooth',
    },
    grid: {
      borderColor: '#E2E8F0',
      strokeDashArray: 5,
    },
    xaxis: {
      categories: data.map(d => d.month),
      labels: {
        style: {
          colors: '#64748B',
        },
      },
    },
    yaxis: {
      labels: {
        style: {
          colors: '#64748B',
        },
        formatter: (value) => `$${value.toLocaleString()}`,
      },
    },
    tooltip: {
      theme: 'light',
      y: {
        formatter: (value) => `$${value.toLocaleString()}`,
      },
    },
  }), [data]);

  const series = useMemo(() => [{
    name: 'Sales',
    data: data.map(d => d.sales),
  }], [data]);

  return (
    <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-6">
      <h3 className="text-title-md font-semibold text-gray-900 dark:text-white mb-6">
        Monthly Sales
      </h3>
      <ReactApexChart
        options={options}
        series={series}
        type="line"
        height={350}
      />
    </div>
  );
};
```

### 5. Create a Modal

**Confirmation Modal Pattern**:
```tsx
import { useState } from 'react';
import { Modal } from '../components/ui/modal';
import { Button } from '../components/ui/button/Button';

const DeleteProductModal = ({ productId, productName, onConfirm, onCancel }) => {
  const [isDeleting, setIsDeleting] = useState(false);

  const handleDelete = async () => {
    setIsDeleting(true);
    try {
      await onConfirm(productId);
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <Modal isOpen={true} onClose={onCancel}>
      <div className="p-6">
        <h3 className="text-title-md font-semibold text-gray-900 dark:text-white mb-4">
          Confirm Delete
        </h3>
        <p className="text-body dark:text-bodydark mb-6">
          Are you sure you want to delete <strong>{productName}</strong>? This action cannot be undone.
        </p>
        <div className="flex gap-3 justify-end">
          <Button
            variant="ghost"
            onClick={onCancel}
            disabled={isDeleting}
          >
            Cancel
          </Button>
          <Button
            variant="danger"
            onClick={handleDelete}
            disabled={isDeleting}
          >
            {isDeleting ? 'Deleting...' : 'Delete Product'}
          </Button>
        </div>
      </div>
    </Modal>
  );
};
```

## TypeScript Best Practices

### Type-Only Imports (REQUIRED)

```tsx
// ✅ CORRECT - Use type-only imports
import { type FC, type ReactNode, type ChangeEvent } from 'react';
import { type ApexOptions } from 'apexcharts';
import { type PayloadAction } from '@reduxjs/toolkit';

// ❌ WRONG - Will fail with verbatimModuleSyntax
import { FC, ReactNode, ChangeEvent } from 'react';
```

### Component Typing

```tsx
// ✅ CORRECT - Proper interface
interface ProductCardProps {
  product: Product;
  onEdit: (id: number) => void;
  onDelete: (id: number) => void;
  className?: string;
}

export const ProductCard: FC<ProductCardProps> = ({
  product,
  onEdit,
  onDelete,
  className = '',
}) => {
  // Implementation
};

// ❌ WRONG - No types
export const ProductCard = (props) => {
  // Implementation
};
```

## Styling Guidelines

### Dark Mode Support

**Always provide dark mode variants**:
```tsx
<div className="bg-white dark:bg-boxdark text-gray-900 dark:text-white border border-stroke dark:border-strokedark">
  Content
</div>
```

### Responsive Design

**Mobile-first approach**:
```tsx
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4 lg:p-6">
  {/* Cards */}
</div>
```

### Conditional Classes

**Use clsx for complex conditions**:
```tsx
import { clsx } from 'clsx';

<button
  className={clsx(
    'px-4 py-2 rounded-lg font-medium transition',
    isActive && 'bg-primary text-white',
    !isActive && 'bg-gray-100 text-gray-700 hover:bg-gray-200',
    isDisabled && 'opacity-50 cursor-not-allowed'
  )}
>
```

## State Management

### RTK Query for API Data

```tsx
// src/services/products.ts
import { apiClient } from './apiClient';
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const productsApi = createApi({
  reducerPath: 'productsApi',
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
    getProducts: builder.query<ProductsResponse, { page?: number; perPage?: number }>({
      query: ({ page = 1, perPage = 20 }) => `/v1/products?page=${page}&per_page=${perPage}`,
      providesTags: ['Product'],
    }),
    createProduct: builder.mutation<Product, Partial<Product>>({
      query: (body) => ({
        url: '/v1/products',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['Product'],
    }),
  }),
});

export const { useGetProductsQuery, useCreateProductMutation } = productsApi;
```

### Redux for Global State

```tsx
// Use for auth, theme, UI state
const { user, currentStore } = useAppSelector((state) => state.auth);
const dispatch = useAppDispatch();
```

## Performance Optimization

### Lazy Load Pages

```tsx
// src/App.tsx
import { lazy, Suspense } from 'react';

const ProductsPage = lazy(() => import('./pages/Products'));
const OrdersPage = lazy(() => import('./pages/Orders'));

<Suspense fallback={<div>Loading...</div>}>
  <Route path="/products" element={<ProductsPage />} />
</Suspense>
```

### Memoization

```tsx
import { useMemo, useCallback } from 'react';

// Expensive computation
const sortedData = useMemo(
  () => data.sort((a, b) => b.price - a.price),
  [data]
);

// Event handler
const handleClick = useCallback(
  (id: number) => {
    dispatch(deleteProduct(id));
  },
  [dispatch]
);
```

## Common Pitfalls

### ❌ DON'T: Inline Object/Array Creation

```tsx
// BAD - Creates new object on every render
<Component options={{ foo: 'bar' }} />
<Component items={[1, 2, 3]} />

// GOOD - Memoize or move outside component
const options = { foo: 'bar' };
const items = [1, 2, 3];
<Component options={options} items={items} />
```

### ❌ DON'T: String Concatenation for Classes

```tsx
// BAD
<div className={'btn ' + (isActive ? 'active' : '')} />

// GOOD
<div className={clsx('btn', isActive && 'active')} />
```

### ❌ DON'T: Forget Dark Mode

```tsx
// BAD - No dark mode
<div className="bg-white text-black">

// GOOD - Dark mode support
<div className="bg-white dark:bg-boxdark text-gray-900 dark:text-white">
```

## Resources

- **Design System Docs**: `docs/19-admin-panel-design-system.md`
- **Tailwind CSS Docs**: https://tailwindcss.com/docs
- **ApexCharts Docs**: https://apexcharts.com/docs/
- **React 19 Docs**: https://react.dev

## Checklist for New Pages

- [ ] Create page component in `src/pages/[Section]/`
- [ ] Add route to `src/App.tsx`
- [ ] Update sidebar menu in `src/layout/AppSidebar.tsx` (if needed)
- [ ] Implement proper TypeScript types
- [ ] Add dark mode support (`dark:` classes)
- [ ] Make responsive (`sm:`, `md:`, `lg:` breakpoints)
- [ ] Use RTK Query for API calls (not manual fetch)
- [ ] Test with actual backend API
- [ ] Verify accessibility (keyboard navigation, ARIA labels)
- [ ] Check bundle size impact (`npm run build`)

## Example: Complete CRUD Page

See `src/pages/Products/` for a complete example implementing:
- List view with table
- Create form
- Edit form
- Delete confirmation
- Search and filtering
- Pagination
- Loading states
- Error handling
- Dark mode
- Responsive design
