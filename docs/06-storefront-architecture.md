# Storefront Architecture - Next.js

## Overview

The storefront is built with Next.js 14+ using the App Router and Static Export (SSG) for maximum performance and SEO optimization. It provides the customer-facing e-commerce experience.

## Technology Stack

### Core
- **Next.js** 14+ (App Router)
- **React** 18.2+
- **TypeScript** 5.0+
- **Static Export** (`output: 'export'`)

### Styling
- **Tailwind CSS** 3.4+
- **CSS Modules** (for component-specific styles)
- **Headless UI** / **Radix UI** (Accessible components)

### State Management
- **Zustand** (Lightweight store for cart, UI state)
- **React Context** (Theme, user preferences)
- **SWR** / **React Query** (Server state, API calls)

### Forms & Validation
- **React Hook Form**
- **Zod** (Schema validation)

### Additional Libraries
- **SWR** (Data fetching with caching)
- **Framer Motion** (Animations)
- **Swiper** (Image carousels)
- **react-hot-toast** (Notifications)
- **Stripe.js** / **PayPal SDK** (Payment processing)

## Project Structure

```
storefront/
├── public/
│   ├── images/
│   ├── fonts/
│   └── favicon.ico
├── src/
│   ├── app/
│   │   ├── (store)/
│   │   │   ├── layout.tsx
│   │   │   ├── page.tsx           # Home page
│   │   │   ├── products/
│   │   │   │   ├── page.tsx       # Product listing
│   │   │   │   └── [slug]/
│   │   │   │       └── page.tsx   # Product detail
│   │   │   ├── categories/
│   │   │   │   └── [slug]/
│   │   │   │       └── page.tsx
│   │   │   ├── cart/
│   │   │   │   └── page.tsx
│   │   │   ├── checkout/
│   │   │   │   └── page.tsx
│   │   │   ├── account/
│   │   │   │   ├── page.tsx       # Account dashboard
│   │   │   │   ├── orders/
│   │   │   │   ├── addresses/
│   │   │   │   └── profile/
│   │   │   ├── about/
│   │   │   ├── contact/
│   │   │   └── [...not-found]/
│   │   ├── api/                    # API routes (for BFF pattern)
│   │   │   ├── cart/
│   │   │   ├── checkout/
│   │   │   └── webhooks/
│   │   ├── globals.css
│   │   └── layout.tsx
│   ├── components/
│   │   ├── common/
│   │   │   ├── Button/
│   │   │   ├── Input/
│   │   │   ├── Card/
│   │   │   ├── Modal/
│   │   │   └── ...
│   │   ├── layout/
│   │   │   ├── Header/
│   │   │   ├── Footer/
│   │   │   ├── Navigation/
│   │   │   └── MobileMenu/
│   │   ├── product/
│   │   │   ├── ProductCard/
│   │   │   ├── ProductGrid/
│   │   │   ├── ProductDetail/
│   │   │   ├── ProductGallery/
│   │   │   └── ProductVariantSelector/
│   │   ├── cart/
│   │   │   ├── CartItem/
│   │   │   ├── CartSummary/
│   │   │   └── CartDrawer/
│   │   └── checkout/
│   │       ├── CheckoutForm/
│   │       ├── ShippingForm/
│   │       ├── PaymentForm/
│   │       └── OrderSummary/
│   ├── lib/
│   │   ├── api/
│   │   │   ├── client.ts
│   │   │   └── endpoints.ts
│   │   ├── utils/
│   │   │   ├── formatters.ts
│   │   │   ├── validators.ts
│   │   │   └── helpers.ts
│   │   └── constants.ts
│   ├── hooks/
│   │   ├── useCart.ts
│   │   ├── useProducts.ts
│   │   ├── useCheckout.ts
│   │   └── useAuth.ts
│   ├── store/
│   │   ├── cartStore.ts
│   │   ├── uiStore.ts
│   │   └── userStore.ts
│   ├── types/
│   │   ├── product.ts
│   │   ├── cart.ts
│   │   ├── order.ts
│   │   └── ...
│   └── middleware.ts
├── .env.local
├── .eslintrc.json
├── next.config.js
├── package.json
├── tailwind.config.ts
├── tsconfig.json
└── README.md
```

## Static Export Configuration

### next.config.js

```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',  // Enable static export
  
  // Image optimization disabled for static export
  // Use external CDN or optimized images
  images: {
    unoptimized: true,
    // Alternative: Use a CDN loader
    // loader: 'custom',
    // loaderFile: './src/lib/imageLoader.ts',
  },
  
  // Trailing slash for better static hosting compatibility
  trailingSlash: true,
  
  // Environment variables available to the browser
  env: {
    NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL,
    NEXT_PUBLIC_STORE_ID: process.env.NEXT_PUBLIC_STORE_ID,
    NEXT_PUBLIC_STRIPE_KEY: process.env.NEXT_PUBLIC_STRIPE_KEY,
  },
};

module.exports = nextConfig;
```

### Build Output

```
out/
├── index.html
├── products/
│   ├── index.html
│   └── product-slug/
│       └── index.html
├── categories/
├── cart/
│   └── index.html
├── _next/
│   ├── static/
│   │   ├── css/
│   │   ├── chunks/
│   │   └── media/
│   └── ...
└── ...
```

## Data Fetching Strategy

### Static Generation (SSG)

**For relatively stable content**: Product listings, category pages, static pages

```typescript
// app/(store)/products/[slug]/page.tsx
import { Product } from '@/types/product';
import { getProduct, getAllProducts } from '@/lib/api/products';

// Generate static params at build time
export async function generateStaticParams() {
  const products = await getAllProducts();
  
  return products.map((product) => ({
    slug: product.slug,
  }));
}

// Fetch data for each product page
export default async function ProductPage({
  params,
}: {
  params: { slug: string };
}) {
  const product = await getProduct(params.slug);
  
  if (!product) {
    notFound();
  }
  
  return <ProductDetail product={product} />;
}

// Revalidate every 1 hour (for CDN/hosting with revalidation support)
export const revalidate = 3600;
```

### Client-Side Fetching

**For dynamic/personalized content**: Cart, checkout, user account, real-time inventory

```typescript
// hooks/useCart.ts
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface CartItem {
  productId: number;
  variantId?: number;
  quantity: number;
  price: number;
}

interface CartStore {
  items: CartItem[];
  addItem: (item: CartItem) => void;
  removeItem: (productId: number) => void;
  updateQuantity: (productId: number, quantity: number) => void;
  clearCart: () => void;
  getTotalPrice: () => number;
}

export const useCartStore = create<CartStore>()(
  persist(
    (set, get) => ({
      items: [],
      
      addItem: (item) => set((state) => {
        const existingItem = state.items.find(
          (i) => i.productId === item.productId && i.variantId === item.variantId
        );
        
        if (existingItem) {
          return {
            items: state.items.map((i) =>
              i.productId === item.productId && i.variantId === item.variantId
                ? { ...i, quantity: i.quantity + item.quantity }
                : i
            ),
          };
        }
        
        return { items: [...state.items, item] };
      }),
      
      removeItem: (productId) => set((state) => ({
        items: state.items.filter((i) => i.productId !== productId),
      })),
      
      updateQuantity: (productId, quantity) => set((state) => ({
        items: state.items.map((i) =>
          i.productId === productId ? { ...i, quantity } : i
        ),
      })),
      
      clearCart: () => set({ items: [] }),
      
      getTotalPrice: () => {
        const items = get().items;
        return items.reduce((total, item) => total + item.price * item.quantity, 0);
      },
    }),
    {
      name: 'cart-storage',
    }
  )
);
```

### Hybrid Approach

**For frequently changing data**: Inventory status, prices

```typescript
// components/product/ProductCard.tsx
'use client';

import { useState, useEffect } from 'react';
import useSWR from 'swr';
import { Product } from '@/types/product';
import { checkInventory } from '@/lib/api/inventory';

interface ProductCardProps {
  product: Product;
}

export function ProductCard({ product }: ProductCardProps) {
  // Static product data from SSG
  const { name, slug, price, images } = product;
  
  // Dynamic inventory data fetched on client
  const { data: inventory, isLoading } = useSWR(
    `/api/inventory/${product.id}`,
    () => checkInventory(product.id),
    { refreshInterval: 30000 } // Refresh every 30 seconds
  );
  
  const isInStock = inventory?.available_quantity > 0;
  
  return (
    <div className="product-card">
      <img src={images[0]?.url} alt={name} />
      <h3>{name}</h3>
      <p>${price}</p>
      <div>
        {isLoading ? (
          <span>Checking stock...</span>
        ) : isInStock ? (
          <button>Add to Cart</button>
        ) : (
          <span className="text-red-500">Out of Stock</span>
        )}
      </div>
    </div>
  );
}
```

## Key Pages & Components

### Home Page

```typescript
// app/(store)/page.tsx
import { getFeaturedProducts, getCategories } from '@/lib/api';
import { Hero } from '@/components/home/Hero';
import { FeaturedProducts } from '@/components/home/FeaturedProducts';
import { Categories } from '@/components/home/Categories';
import { Newsletter } from '@/components/home/Newsletter';

export default async function HomePage() {
  const [featuredProducts, categories] = await Promise.all([
    getFeaturedProducts(),
    getCategories(),
  ]);
  
  return (
    <>
      <Hero />
      <FeaturedProducts products={featuredProducts} />
      <Categories categories={categories} />
      <Newsletter />
    </>
  );
}

export const metadata = {
  title: 'Home - Your Store',
  description: 'Shop the best products at great prices',
};
```

### Product Listing Page

```typescript
// app/(store)/products/page.tsx
import { getProducts } from '@/lib/api/products';
import { ProductGrid } from '@/components/product/ProductGrid';
import { ProductFilters } from '@/components/product/ProductFilters';

interface SearchParams {
  category?: string;
  sort?: string;
  page?: string;
}

export default async function ProductsPage({
  searchParams,
}: {
  searchParams: SearchParams;
}) {
  const products = await getProducts({
    category: searchParams.category,
    sort: searchParams.sort,
    page: searchParams.page ? parseInt(searchParams.page) : 1,
  });
  
  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-8">Products</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
        <aside className="md:col-span-1">
          <ProductFilters />
        </aside>
        
        <main className="md:col-span-3">
          <ProductGrid products={products.data} />
        </main>
      </div>
    </div>
  );
}
```

### Product Detail Page

```typescript
// app/(store)/products/[slug]/page.tsx
import { getProduct } from '@/lib/api/products';
import { ProductGallery } from '@/components/product/ProductGallery';
import { ProductInfo } from '@/components/product/ProductInfo';
import { AddToCartButton } from '@/components/product/AddToCartButton';
import { RelatedProducts } from '@/components/product/RelatedProducts';

export async function generateStaticParams() {
  const products = await getAllProducts();
  return products.map((p) => ({ slug: p.slug }));
}

export default async function ProductDetailPage({
  params,
}: {
  params: { slug: string };
}) {
  const product = await getProduct(params.slug);
  
  if (!product) {
    notFound();
  }
  
  return (
    <div className="container mx-auto px-4 py-8">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <ProductGallery images={product.images} />
        
        <div>
          <h1 className="text-3xl font-bold mb-4">{product.name}</h1>
          <p className="text-2xl font-semibold text-gray-900 mb-6">
            ${product.price}
          </p>
          
          <div className="prose mb-6">
            <p>{product.description}</p>
          </div>
          
          <AddToCartButton product={product} />
        </div>
      </div>
      
      <RelatedProducts productId={product.id} />
    </div>
  );
}

export async function generateMetadata({
  params,
}: {
  params: { slug: string };
}) {
  const product = await getProduct(params.slug);
  
  return {
    title: product.meta_title || product.name,
    description: product.meta_description || product.short_description,
    openGraph: {
      title: product.name,
      description: product.short_description,
      images: [product.images[0]?.url],
    },
  };
}
```

### Cart Page

```typescript
// app/(store)/cart/page.tsx
'use client';

import { useCartStore } from '@/hooks/useCart';
import { CartItem } from '@/components/cart/CartItem';
import { CartSummary } from '@/components/cart/CartSummary';
import Link from 'next/link';

export default function CartPage() {
  const { items, removeItem, updateQuantity } = useCartStore();
  
  if (items.length === 0) {
    return (
      <div className="container mx-auto px-4 py-16 text-center">
        <h1 className="text-2xl font-bold mb-4">Your cart is empty</h1>
        <Link href="/products">
          <button className="btn-primary">Continue Shopping</button>
        </Link>
      </div>
    );
  }
  
  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-8">Shopping Cart</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div className="md:col-span-2">
          {items.map((item) => (
            <CartItem
              key={`${item.productId}-${item.variantId}`}
              item={item}
              onRemove={() => removeItem(item.productId)}
              onUpdateQuantity={(qty) => updateQuantity(item.productId, qty)}
            />
          ))}
        </div>
        
        <div>
          <CartSummary />
          <Link href="/checkout">
            <button className="btn-primary w-full">
              Proceed to Checkout
            </button>
          </Link>
        </div>
      </div>
    </div>
  );
}
```

### Checkout Page

```typescript
// app/(store)/checkout/page.tsx
'use client';

import { useState } from 'react';
import { useCartStore } from '@/hooks/useCart';
import { CheckoutForm } from '@/components/checkout/CheckoutForm';
import { OrderSummary } from '@/components/checkout/OrderSummary';
import { loadStripe } from '@stripe/stripe-js';
import { Elements } from '@stripe/react-stripe-js';

const stripePromise = loadStripe(process.env.NEXT_PUBLIC_STRIPE_KEY!);

export default function CheckoutPage() {
  const { items, getTotalPrice, clearCart } = useCartStore();
  const [isProcessing, setIsProcessing] = useState(false);
  
  const handleCheckout = async (formData: any) => {
    setIsProcessing(true);
    
    try {
      const response = await fetch('/api/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          items,
          ...formData,
        }),
      });
      
      const data = await response.json();
      
      if (data.success) {
        clearCart();
        // Redirect to success page
        window.location.href = `/order-confirmation/${data.orderId}`;
      }
    } catch (error) {
      console.error('Checkout error:', error);
    } finally {
      setIsProcessing(false);
    }
  };
  
  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-8">Checkout</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div className="md:col-span-2">
          <Elements stripe={stripePromise}>
            <CheckoutForm
              onSubmit={handleCheckout}
              isProcessing={isProcessing}
            />
          </Elements>
        </div>
        
        <div>
          <OrderSummary items={items} total={getTotalPrice()} />
        </div>
      </div>
    </div>
  );
}
```

## API Routes (Backend for Frontend)

```typescript
// app/api/checkout/route.ts
import { NextRequest, NextResponse } from 'next/server';
import { createOrder } from '@/lib/api/orders';
import { processPayment } from '@/lib/payments/stripe';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { items, customer, billing_address, shipping_address, payment_method } = body;
    
    // Create order in backend
    const order = await createOrder({
      items,
      customer,
      billing_address,
      shipping_address,
    });
    
    // Process payment
    const payment = await processPayment({
      orderId: order.id,
      amount: order.total,
      payment_method,
    });
    
    if (payment.status === 'succeeded') {
      return NextResponse.json({
        success: true,
        orderId: order.id,
        orderNumber: order.order_number,
      });
    }
    
    return NextResponse.json(
      { success: false, error: 'Payment failed' },
      { status: 400 }
    );
  } catch (error) {
    console.error('Checkout error:', error);
    return NextResponse.json(
      { success: false, error: 'Internal server error' },
      { status: 500 }
    );
  }
}
```

## SEO Optimization

### Metadata Configuration

```typescript
// app/(store)/layout.tsx
import { Metadata } from 'next';

export const metadata: Metadata = {
  title: {
    template: '%s | Your Store',
    default: 'Your Store - Best Products Online',
  },
  description: 'Shop the best products at great prices',
  keywords: ['ecommerce', 'shopping', 'products'],
  authors: [{ name: 'Your Store' }],
  openGraph: {
    type: 'website',
    locale: 'en_US',
    url: 'https://yourstore.com',
    siteName: 'Your Store',
  },
  twitter: {
    card: 'summary_large_image',
    site: '@yourstore',
  },
};
```

### Sitemap & Robots.txt

```typescript
// app/sitemap.ts
import { MetadataRoute } from 'next';
import { getAllProducts, getCategories } from '@/lib/api';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = 'https://yourstore.com';
  
  const products = await getAllProducts();
  const categories = await getCategories();
  
  const productUrls = products.map((product) => ({
    url: `${baseUrl}/products/${product.slug}`,
    lastModified: product.updated_at,
    changeFrequency: 'daily' as const,
    priority: 0.8,
  }));
  
  const categoryUrls = categories.map((category) => ({
    url: `${baseUrl}/categories/${category.slug}`,
    lastModified: new Date(),
    changeFrequency: 'weekly' as const,
    priority: 0.6,
  }));
  
  return [
    {
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    ...productUrls,
    ...categoryUrls,
  ];
}
```

## Deployment

### Build Command

```bash
npm run build
```

### Deploy to Static Hosting

**Vercel**:
```bash
vercel deploy --prod
```

**Netlify**:
```bash
netlify deploy --prod --dir=out
```

**AWS S3 + CloudFront**:
```bash
aws s3 sync out/ s3://your-bucket-name/
aws cloudfront create-invalidation --distribution-id YOUR_DIST_ID --paths "/*"
```

**Docker + Nginx**:
```dockerfile
FROM nginx:alpine
COPY out/ /usr/share/nginx/html/
COPY nginx.conf /etc/nginx/nginx.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

## Performance Optimization

1. **Image Optimization**: Use WebP, lazy loading, CDN
2. **Code Splitting**: Automatic with Next.js App Router
3. **Prefetching**: Link prefetching for faster navigation
4. **Caching**: Aggressive caching with CDN
5. **Bundle Size**: Tree-shaking, dynamic imports
6. **Critical CSS**: Inline critical CSS

## Next Steps

1. Review [Multi-Tenancy Strategy](07-multi-tenancy.md)
2. Review [Scalability & Performance](08-scalability.md)
3. Review [Development Roadmap](10-development-roadmap.md)
4. Set up Next.js project
