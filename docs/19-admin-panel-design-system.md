# Admin Panel Design System

**Version**: 1.0  
**Last Updated**: April 7, 2026  
**Framework**: React 19 + TypeScript 6 + Tailwind CSS 4  
**Template**: TailAdmin Pro

---

## Table of Contents

1. [Overview](#overview)
2. [Technology Stack](#technology-stack)
3. [Color System](#color-system)
4. [Typography](#typography)
5. [Spacing & Sizing](#spacing--sizing)
6. [Components](#components)
7. [Layout Structure](#layout-structure)
8. [Dark Mode](#dark-mode)
9. [Icons](#icons)
10. [Best Practices](#best-practices)

---

## Overview

The E-Commerce Admin Panel uses a clean, modern design system based on **TailAdmin Pro**, a professional dashboard template. The system prioritizes:

- **Consistency**: Unified design language across all pages
- **Accessibility**: WCAG 2.1 Level AA compliance
- **Responsiveness**: Mobile-first approach (breakpoints: sm, md, lg, xl, 2xl)
- **Dark Mode**: Full dark mode support via class-based theming
- **Performance**: Lightweight components, optimized bundle size

**Core Philosophy**: Minimal, functional, and easy to maintain for multi-tenant e-commerce platform.

---

## Technology Stack

### Core Dependencies

```json
{
  "react": "^19.2.4",
  "react-dom": "^19.2.4",
  "typescript": "~6.0.2",
  "tailwindcss": "^4.0.8",
  "vite": "^8.0.4"
}
```

### UI Libraries

| Library | Version | Purpose |
|---------|---------|---------|
| **React Router** | 7.14.0 | Client-side routing |
| **Redux Toolkit** | 2.11.2 | State management |
| **ApexCharts** | 4.1.0 | Data visualization |
| **Flatpickr** | 4.6.13 | Date/time picker |
| **React Dropzone** | 14.3.5 | File uploads |
| **Swiper** | 11.2.3 | Carousels/sliders |
| **FullCalendar** | 6.1.15 | Calendar component |

### Build Tools

- **Vite 8**: Lightning-fast dev server and bundler
- **vite-plugin-svgr**: SVG React components
- **@tailwindcss/postcss**: Tailwind CSS 4 PostCSS plugin
- **TypeScript 6**: Strict type checking with `verbatimModuleSyntax`

---

## Color System

### Brand Colors

```javascript
// Primary brand colors
primary: '#3C50E0',    // Electric blue (buttons, links, primary actions)
secondary: '#80CAEE',  // Light blue (secondary actions, accents)
```

### Semantic Colors

```javascript
// Status colors
success: '#10B981',    // Green (success states, checkmarks)
warning: '#FBBF24',    // Amber (warnings, pending states)
danger: '#EF4444',     // Red (errors, destructive actions)
```

### Grayscale (Light Mode)

```javascript
// Main grays
dark: '#1C2434',       // Primary text
body: '#64748B',       // Body text
stroke: '#E2E8F0',     // Borders
gray: '#EFF4FB',       // Backgrounds
whiten: '#F1F5F9',     // Subtle backgrounds
```

### Dark Mode Colors

```javascript
// Dark mode specific
boxdark: '#24303F',    // Card backgrounds
strokedark: '#2E3A47', // Borders in dark mode
graydark: '#333A48',   // Dark background
bodydark: '#AEB7C0',   // Body text in dark mode
```

### Usage Examples

```tsx
// Button primary
<button className="bg-primary hover:bg-primary/90 text-white">
  Save
</button>

// Success alert
<div className="bg-success/10 border border-success text-success-700">
  Operation successful
</div>

// Danger button
<button className="bg-danger hover:bg-danger/90 text-white">
  Delete
</button>
```

---

## Typography

### Font Family

**Default**: System font stack for optimal performance
```css
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

### Heading Scale

| Class | Size | Line Height | Usage |
|-------|------|-------------|-------|
| `text-title-xxl` | 44px | 55px | Hero headings |
| `text-title-xl` | 36px | 45px | Page titles |
| `text-title-xl2` | 33px | 45px | Section titles |
| `text-title-lg` | 28px | 35px | Large headings |
| `text-title-md` | 24px | 30px | Medium headings |
| `text-title-sm` | 20px | 26px | Small headings |
| `text-title-xsm` | 18px | 24px | Extra small headings |

### Body Text

```tsx
// Default body text
<p className="text-body dark:text-bodydark">
  Regular paragraph text
</p>

// Small text (captions, metadata)
<span className="text-theme-xs text-gray-500">
  Last updated 2 hours ago
</span>

// Medium body text
<div className="text-theme-sm text-gray-700 dark:text-gray-300">
  Description text
</div>
```

### Font Weights

```tsx
// Regular (400)
<span className="font-normal">Regular text</span>

// Medium (500)
<span className="font-medium">Medium emphasis</span>

// Semibold (600)
<span className="font-semibold">Section headers</span>

// Bold (700)
<span className="font-bold">Strong emphasis</span>
```

---

## Spacing & Sizing

### Spacing Scale

TailAdmin extends Tailwind's default spacing with fine-grained increments:

```javascript
// Custom spacing (in rem)
4.5  → 1.125rem  (18px)
5.5  → 1.375rem  (22px)
6.5  → 1.625rem  (26px)
7.5  → 1.875rem  (30px)
// ... up to 242.5 → 60.625rem (970px)
```

### Common Spacing Patterns

```tsx
// Card padding
<div className="p-6 sm:p-8">Content</div>

// Stack spacing
<div className="space-y-4">
  <div>Item 1</div>
  <div>Item 2</div>
</div>

// Grid gap
<div className="grid grid-cols-3 gap-4">
  ...
</div>
```

### Max Width Constraints

```tsx
// Form elements
<input className="max-w-125" /> {/* 500px */}

// Content containers
<div className="max-w-270 mx-auto"> {/* 1080px */}
  Main content
</div>
```

---

## Components

### Core UI Components

#### 1. **Button**

**Location**: `src/components/ui/button/Button.tsx`

```tsx
import { Button } from '../components/ui/button/Button';

// Primary button
<Button variant="primary" size="md">
  Save Changes
</Button>

// Secondary button
<Button variant="secondary" size="sm">
  Cancel
</Button>

// Danger button
<Button variant="danger" size="lg">
  Delete Account
</Button>
```

**Variants**: `primary`, `secondary`, `success`, `warning`, `danger`, `ghost`  
**Sizes**: `sm`, `md`, `lg`

#### 2. **Alert**

**Location**: `src/components/ui/alert/Alert.tsx`

```tsx
import { Alert } from '../components/ui/alert/Alert';

<Alert type="success">
  Product created successfully!
</Alert>

<Alert type="danger">
  Error: Unable to save changes
</Alert>

<Alert type="warning">
  Low stock alert for this product
</Alert>
```

**Types**: `success`, `danger`, `warning`, `info`

#### 3. **Badge**

**Location**: `src/components/ui/badge/Badge.tsx`

```tsx
import { Badge } from '../components/ui/badge/Badge';

<Badge variant="success">Active</Badge>
<Badge variant="danger">Out of Stock</Badge>
<Badge variant="warning">Pending</Badge>
```

#### 4. **Dropdown**

**Location**: `src/components/ui/dropdown/Dropdown.tsx`

```tsx
import { Dropdown } from '../components/ui/dropdown/Dropdown';
import { DropdownItem } from '../components/ui/dropdown/DropdownItem';

<Dropdown isOpen={isOpen} onClose={closeDropdown}>
  <DropdownItem tag="a" to="/profile">
    Edit Profile
  </DropdownItem>
  <DropdownItem tag="button" onClick={handleAction}>
    Archive
  </DropdownItem>
</Dropdown>
```

#### 5. **Table**

**Location**: `src/components/ui/table/index.tsx`

```tsx
import { Table } from '../components/ui/table';

<Table>
  <thead>
    <tr>
      <th>Product</th>
      <th>Price</th>
      <th>Stock</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Blue T-Shirt</td>
      <td>$29.99</td>
      <td>145</td>
    </tr>
  </tbody>
</Table>
```

#### 6. **Modal**

**Location**: `src/components/ui/modal/index.tsx`

```tsx
import { Modal } from '../components/ui/modal';

<Modal isOpen={showModal} onClose={closeModal}>
  <h3 className="text-title-md font-semibold mb-4">
    Confirm Delete
  </h3>
  <p className="text-body mb-6">
    Are you sure you want to delete this product?
  </p>
  <div className="flex gap-3">
    <Button variant="danger">Delete</Button>
    <Button variant="ghost" onClick={closeModal}>Cancel</Button>
  </div>
</Modal>
```

#### 7. **Avatar**

**Location**: `src/components/ui/avatar/Avatar.tsx`

```tsx
import { Avatar } from '../components/ui/avatar/Avatar';

// Image avatar
<Avatar src="/images/user.jpg" alt="John Doe" />

// Initials avatar (auto-generated)
<Avatar name="John Doe" />

// With status indicator
<Avatar src="/images/user.jpg" status="online" />
```

### Form Components

#### Input Fields

```tsx
// Text input
<input
  type="text"
  className="w-full rounded-lg border border-stroke bg-white py-3 px-4.5 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
  placeholder="Product name"
/>

// Text area
<textarea
  className="w-full rounded-lg border border-stroke bg-white py-3 px-4.5 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
  rows={4}
  placeholder="Description"
/>

// Select dropdown
<select className="w-full rounded-lg border border-stroke bg-white py-3 px-4.5 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white">
  <option>Select category</option>
  <option>Electronics</option>
  <option>Clothing</option>
</select>
```

#### Checkbox & Radio

```tsx
// Checkbox
<label className="flex items-center gap-2 cursor-pointer">
  <input
    type="checkbox"
    className="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary"
  />
  <span>Remember me</span>
</label>

// Radio
<label className="flex items-center gap-2 cursor-pointer">
  <input
    type="radio"
    name="status"
    className="w-5 h-5 text-primary focus:ring-primary"
  />
  <span>Active</span>
</label>
```

#### File Upload (Dropzone)

```tsx
import { useDropzone } from 'react-dropzone';

const { getRootProps, getInputProps } = useDropzone({
  accept: { 'image/*': [] },
  onDrop: handleFileDrop,
});

<div
  {...getRootProps()}
  className="border-2 border-dashed border-stroke rounded-lg p-8 text-center cursor-pointer hover:border-primary"
>
  <input {...getInputProps()} />
  <p>Drag & drop images here, or click to select</p>
</div>
```

### Data Visualization

#### ApexCharts Integration

```tsx
import ReactApexChart from 'react-apexcharts';
import { type ApexOptions } from 'apexcharts';

const options: ApexOptions = {
  chart: { type: 'line', toolbar: { show: false } },
  colors: ['#3C50E0'],
  stroke: { width: 2, curve: 'smooth' },
  xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May'] },
};

const series = [{
  name: 'Sales',
  data: [30, 40, 35, 50, 49],
}];

<ReactApexChart options={options} series={series} type="line" height={350} />
```

**Available Chart Types**:
- Line charts (sales trends)
- Bar charts (category comparisons)
- Pie/Donut charts (distribution)
- Area charts (cumulative data)

---

## Layout Structure

### App Layout

```
┌─────────────────────────────────────────┐
│           AppHeader (fixed)             │ ← 64px height
├──────────┬──────────────────────────────┤
│          │                              │
│          │                              │
│ AppSide- │    Page Content              │
│ bar      │    (Outlet)                  │ ← Scrollable
│          │                              │
│ 290px    │                              │
│ (expand) │                              │
│          │                              │
│ 90px     │                              │
│ (collap) │                              │
└──────────┴──────────────────────────────┘
```

### AppHeader

**Location**: `src/layout/AppHeader.tsx`

**Features**:
- Sidebar toggle button (mobile)
- Search bar (desktop)
- Theme toggle (dark/light mode)
- Notifications dropdown
- User dropdown with avatar

### AppSidebar

**Location**: `src/layout/AppSidebar.tsx`

**States**:
- **Expanded**: 290px width (default on desktop)
- **Collapsed**: 90px width (icons only)
- **Mobile**: Full overlay with backdrop

**Features**:
- Auto-collapse on hover (collapsed state)
- Active route highlighting
- Submenu expansion
- Gradient logo icon
- Smooth transitions

**Menu Structure**:
```tsx
const navItems = [
  { icon: <GridIcon />, name: "Dashboard", path: "/" },
  {
    icon: <BoxCubeIcon />,
    name: "Products",
    subItems: [
      { name: "All Products", path: "/products" },
      { name: "Categories", path: "/categories" },
      { name: "Add Product", path: "/products/new" },
    ],
  },
  // ... more items
];
```

### Page Layout Pattern

```tsx
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
      
      {/* Content Card */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        {/* Page content */}
      </div>
    </div>
  );
};
```

### Responsive Breakpoints

```javascript
// Tailwind breakpoints
sm: '640px',   // Small devices
md: '768px',   // Tablets
lg: '1024px',  // Laptops
xl: '1280px',  // Desktops
2xl: '1536px', // Large desktops
```

**Usage**:
```tsx
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
  {/* Responsive grid */}
</div>
```

---

## Dark Mode

### Implementation

Dark mode is **class-based**, controlled via `ThemeProvider` context:

```tsx
// ThemeProvider (src/context/ThemeContext.tsx)
<ThemeProvider>
  <App />
</ThemeProvider>
```

### Toggle Component

```tsx
import { useTheme } from '../context/ThemeContext';

const ThemeToggle = () => {
  const { theme, toggleTheme } = useTheme();
  
  return (
    <button onClick={toggleTheme}>
      {theme === 'dark' ? <SunIcon /> : <MoonIcon />}
    </button>
  );
};
```

### Dark Mode Classes

```tsx
// Background
<div className="bg-white dark:bg-gray-900">

// Text
<p className="text-gray-900 dark:text-white">

// Borders
<div className="border-gray-200 dark:border-gray-800">

// Cards
<div className="bg-white dark:bg-boxdark border border-stroke dark:border-strokedark">
```

### Storage

Theme preference is saved to `localStorage`:

```javascript
localStorage.setItem('theme', 'dark');
```

---

## Icons

### SVG Icon System

All icons are **SVG React components** using `vite-plugin-svgr`:

**Location**: `src/icons/`

**Usage**:
```tsx
import { GridIcon, BoxCubeIcon, UserCircleIcon } from '../icons';

<GridIcon className="w-5 h-5 text-primary" />
```

### Icon Library

| Icon | Usage |
|------|-------|
| `GridIcon` | Dashboard |
| `BoxCubeIcon` | Products, Inventory |
| `DollarLineIcon` | Orders, Sales |
| `UserCircleIcon` | Customers, Profile |
| `ShootingStarIcon` | Settings |
| `ChevronDownIcon` | Dropdowns, Expandable menus |

### Custom Icon Pattern

```tsx
// src/icons/CustomIcon.tsx
export const CustomIcon = (props: React.SVGProps<SVGSVGElement>) => (
  <svg
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    {...props}
  >
    <path d="..." fill="currentColor" />
  </svg>
);
```

---

## Best Practices

### 1. Component Development

✅ **DO**:
```tsx
// Type-only imports
import { type FC, type ReactNode } from 'react';

// Proper typing
interface ButtonProps {
  children: ReactNode;
  onClick?: () => void;
  variant?: 'primary' | 'secondary';
}

export const Button: FC<ButtonProps> = ({ children, onClick, variant = 'primary' }) => {
  return (
    <button
      onClick={onClick}
      className={`btn btn-${variant}`}
    >
      {children}
    </button>
  );
};
```

❌ **DON'T**:
```tsx
// Missing types
export const Button = (props) => {
  return <button {...props} />;
};
```

### 2. Styling

✅ **DO**:
```tsx
// Use Tailwind classes
<div className="flex items-center gap-3 p-4 rounded-lg bg-white dark:bg-boxdark">

// Use clsx for conditional classes
import { clsx } from 'clsx';

<button className={clsx(
  'px-4 py-2 rounded-lg',
  isActive && 'bg-primary text-white',
  !isActive && 'bg-gray-100 text-gray-700'
)}>
```

❌ **DON'T**:
```tsx
// Inline styles
<div style={{ padding: '16px', background: '#fff' }}>

// String concatenation
<button className={'btn ' + (isActive ? 'active' : 'inactive')}>
```

### 3. State Management

✅ **DO**:
```tsx
// RTK Query for API data
const { data: products, isLoading } = useGetProductsQuery();

// Redux for global state
const { user, currentStore } = useAppSelector((state) => state.auth);
```

❌ **DON'T**:
```tsx
// Manual fetch in useEffect
const [products, setProducts] = useState([]);
useEffect(() => {
  fetch('/api/products').then(r => r.json()).then(setProducts);
}, []);
```

### 4. Accessibility

✅ **DO**:
```tsx
// Proper ARIA labels
<button aria-label="Close menu" onClick={closeMenu}>
  <CloseIcon />
</button>

// Semantic HTML
<nav>
  <ul>
    <li><a href="/">Home</a></li>
  </ul>
</nav>

// Keyboard navigation
<button
  onKeyDown={(e) => e.key === 'Enter' && handleClick()}
  tabIndex={0}
>
```

❌ **DON'T**:
```tsx
// Div soup
<div onClick={handleClick}>
  <div>Menu item</div>
</div>
```

### 5. Performance

✅ **DO**:
```tsx
// Lazy load routes
const ProductsPage = lazy(() => import('./pages/Products'));

// Memoize expensive computations
const sortedProducts = useMemo(
  () => products.sort((a, b) => b.price - a.price),
  [products]
);

// Debounce search input
const debouncedSearch = useDebounce(searchTerm, 500);
```

❌ **DON'T**:
```tsx
// Inline object/array creation in render
<Component data={[1, 2, 3]} />

// Heavy operations in render
const sorted = products.sort(...);
```

### 6. File Organization

```
src/
├── components/
│   ├── ui/           # Reusable UI components
│   ├── header/       # Header-specific components
│   ├── common/       # Shared components
│   └── ecommerce/    # Business logic components
├── pages/            # Route pages
│   ├── Products/
│   ├── Orders/
│   └── Customers/
├── layout/           # Layout components
├── store/            # Redux store
│   ├── authSlice.ts
│   └── hooks.ts
├── services/         # API services
│   ├── apiClient.ts
│   └── products.ts
├── types/            # TypeScript types
├── icons/            # SVG icons
└── context/          # React contexts
```

---

## Migration Notes

### From Ant Design to TailAdmin

**What Changed**:
- ❌ Removed: Ant Design components (`antd`)
- ✅ Added: Custom TailAdmin components
- ✅ Added: Tailwind CSS 4 with custom theme
- ✅ Preserved: Redux Toolkit, RTK Query, authentication

**Component Mapping**:

| Ant Design | TailAdmin |
|------------|-----------|
| `<Button>` | `<Button>` (custom) |
| `<Table>` | `<Table>` (custom) |
| `<Form>` | Native HTML + Tailwind |
| `<Modal>` | `<Modal>` (custom) |
| `<message.success()>` | `<Alert type="success">` |
| `<Spin>` | Custom loading spinner |

**Example Migration**:

Before (Ant Design):
```tsx
import { Button, message } from 'antd';

const handleSave = async () => {
  try {
    await saveProduct();
    message.success('Product saved!');
  } catch (error) {
    message.error('Failed to save');
  }
};

<Button type="primary" onClick={handleSave}>Save</Button>
```

After (TailAdmin):
```tsx
import { Button } from '../components/ui/button/Button';
import { useState } from 'react';

const [alert, setAlert] = useState<{type: 'success' | 'error', message: string} | null>(null);

const handleSave = async () => {
  try {
    await saveProduct();
    setAlert({ type: 'success', message: 'Product saved!' });
  } catch (error) {
    setAlert({ type: 'error', message: 'Failed to save' });
  }
};

{alert && <Alert type={alert.type}>{alert.message}</Alert>}
<Button variant="primary" onClick={handleSave}>Save</Button>
```

---

## Resources

### Documentation
- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [React 19 Docs](https://react.dev)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [ApexCharts Docs](https://apexcharts.com/docs/)

### Internal Docs
- [API Reference](./API-REFERENCE.md)
- [Getting Started](./11-getting-started.md)
- [Backend Architecture](./02-backend-architecture.md)

### Dev Tools
- **React DevTools**: Browser extension for debugging
- **Redux DevTools**: State inspection
- **Tailwind CSS IntelliSense**: VSCode extension for class autocomplete

---

## Changelog

### Version 1.0 (April 7, 2026)
- Initial design system documentation
- TailAdmin Pro integration complete
- Custom component library established
- Dark mode implementation
- E-commerce-specific menu structure
- Removed Ant Design dependencies
- Bundle size optimized to 1.09 MB (gzip: 312 KB)

---

**Maintained by**: Development Team  
**Last Review**: April 7, 2026  
**Next Review**: Monthly
