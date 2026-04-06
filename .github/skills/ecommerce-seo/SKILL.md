---
name: ecommerce-seo
description: 'Implement SEO features for e-commerce platform to support frontend optimization. Use when: adding products, categories, pages, or any content that needs SEO; implementing meta tags, structured data, sitemaps, or Open Graph support.'
argument-hint: 'Specify "product", "category", "page", "schema", or "sitemap" for SEO implementation'
---

# E-Commerce SEO Implementation

## Purpose

Ensure all backend data structures support comprehensive SEO requirements for the multi-tenant e-commerce platform. SEO is critical for organic traffic and must be built into every content model from the start.

## When to Use

- Creating or updating product models
- Creating or updating category models
- Adding any content pages or blog posts
- Implementing meta tags for any public-facing content
- Setting up structured data (Schema.org, JSON-LD)
- Generating sitemaps
- Configuring Open Graph and Twitter Cards
- Implementing canonical URLs

## Critical SEO Requirements

### 1. Every Public Content Must Have

**Required Fields**:
- ✅ `slug` - URL-friendly identifier (unique per store)
- ✅ `meta_title` - SEO title (50-60 characters)
- ✅ `meta_description` - SEO description (150-160 characters)
- ✅ `meta_keywords` - Optional keywords (comma-separated)
- ✅ `canonical_url` - Optional canonical URL override
- ✅ `og_title` - Open Graph title (override meta_title)
- ✅ `og_description` - Open Graph description
- ✅ `og_image` - Open Graph image URL
- ✅ `twitter_card` - Twitter card type (summary, summary_large_image)
- ✅ `schema_markup` - JSON-LD structured data
- ✅ `robots_meta` - Robots directive (index, noindex, follow, nofollow)

### 2. Product SEO Structure

**Database Fields** (already implemented):
```php
Schema::create('products', function (Blueprint $table) {
    // Basic SEO
    $table->string('slug')->unique();
    $table->string('meta_title')->nullable();
    $table->text('meta_description')->nullable();
    
    // NEEDED: Advanced SEO
    $table->string('meta_keywords')->nullable();
    $table->string('canonical_url')->nullable();
    $table->string('og_title')->nullable();
    $table->text('og_description')->nullable();
    $table->string('og_image')->nullable();
    $table->enum('twitter_card', ['summary', 'summary_large_image'])->default('summary_large_image');
    $table->json('schema_markup')->nullable(); // Product Schema.org
    $table->string('robots_meta')->default('index,follow');
});
```

**Product Schema.org JSON-LD**:
```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Premium Laptop Pro",
  "image": "https://example.com/images/laptop.jpg",
  "description": "High-performance laptop...",
  "sku": "LAP-001",
  "brand": {
    "@type": "Brand",
    "name": "StoreName"
  },
  "offers": {
    "@type": "Offer",
    "url": "https://example.com/products/laptop",
    "priceCurrency": "USD",
    "price": "999.99",
    "availability": "https://schema.org/InStock",
    "seller": {
      "@type": "Organization",
      "name": "StoreName"
    }
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.5",
    "reviewCount": "24"
  }
}
```

### 3. Category SEO Structure

**Database Fields** (need to add):
```php
Schema::create('categories', function (Blueprint $table) {
    // Basic SEO (already have)
    $table->string('slug');
    $table->text('description')->nullable();
    
    // NEEDED: Advanced SEO
    $table->string('meta_title')->nullable();
    $table->text('meta_description')->nullable();
    $table->string('meta_keywords')->nullable();
    $table->string('canonical_url')->nullable();
    $table->string('og_title')->nullable();
    $table->text('og_description')->nullable();
    $table->string('og_image')->nullable();
    $table->json('breadcrumb_schema')->nullable();
    $table->string('robots_meta')->default('index,follow');
});
```

**BreadcrumbList Schema.org JSON-LD**:
```json
{
  "@context": "https://schema.org/",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://example.com"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Electronics",
      "item": "https://example.com/categories/electronics"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Laptops",
      "item": "https://example.com/categories/electronics/laptops"
    }
  ]
}
```

### 4. Store-Level SEO Configuration

**Store Settings** (add to `stores` table `settings` JSON):
```json
{
  "seo": {
    "site_name": "My Store",
    "default_meta_title": "My Store - Shop Online",
    "default_meta_description": "Shop the best products...",
    "home_page_title": "Welcome to My Store",
    "google_site_verification": "verification_code",
    "google_analytics_id": "G-XXXXXXXXXX",
    "facebook_pixel_id": "1234567890",
    "twitter_handle": "@mystore",
    "default_og_image": "https://example.com/og-default.jpg",
    "robots_txt": "User-agent: *\nAllow: /",
    "sitemap_enabled": true,
    "sitemap_change_freq": "daily",
    "sitemap_priority_products": "0.8",
    "sitemap_priority_categories": "0.6"
  }
}
```

### 5. API Response Format for Frontend

**Product API Response** (include SEO data):
```json
{
  "data": {
    "id": 1,
    "name": "Premium Laptop Pro",
    "slug": "premium-laptop-pro",
    "price": "999.99",
    "seo": {
      "meta_title": "Premium Laptop Pro - High Performance | My Store",
      "meta_description": "Buy Premium Laptop Pro with fast shipping...",
      "meta_keywords": "laptop, gaming, premium",
      "canonical_url": null,
      "og_title": "Premium Laptop Pro - Best Deal Online",
      "og_description": "Get the best laptop for gaming and work...",
      "og_image": "https://example.com/images/laptop-og.jpg",
      "twitter_card": "summary_large_image",
      "robots_meta": "index,follow",
      "schema_markup": { /* Product Schema */ },
      "breadcrumbs": [
        {"name": "Home", "url": "/"},
        {"name": "Electronics", "url": "/electronics"},
        {"name": "Laptops", "url": "/electronics/laptops"}
      ]
    }
  }
}
```

### 6. Sitemap Generation

**XML Sitemap Structure**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://example.com/products/premium-laptop-pro</loc>
    <lastmod>2026-04-06</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
</urlset>
```

**Sitemap API Endpoints** (needed):
```php
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-products.xml', [SitemapController::class, 'products']);
Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories']);
```

## Implementation Checklist

### Database Migrations

**Products** (enhance existing):
- [ ] Add `meta_keywords` field
- [ ] Add `canonical_url` field
- [ ] Add `og_title` field
- [ ] Add `og_description` field
- [ ] Add `og_image` field
- [ ] Add `twitter_card` field
- [ ] Add `schema_markup` JSON field
- [ ] Add `robots_meta` field

**Categories** (enhance existing):
- [ ] Add `meta_title` field
- [ ] Add `meta_description` field
- [ ] Add `meta_keywords` field
- [ ] Add `canonical_url` field
- [ ] Add `og_title` field
- [ ] Add `og_description` field
- [ ] Add `og_image` field
- [ ] Add `breadcrumb_schema` JSON field
- [ ] Add `robots_meta` field

**Stores** (enhance settings JSON):
- [ ] Add SEO configuration to `settings` JSON
- [ ] Add Google Analytics ID
- [ ] Add Facebook Pixel ID
- [ ] Add Twitter handle
- [ ] Add default OG image
- [ ] Add sitemap configuration

### Service Layer

**SEO Service**:
```php
class SeoService
{
    /**
     * Generate product schema markup
     */
    public function generateProductSchema(Product $product): array
    {
        return [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->name,
            'image' => $product->primaryImage?->url,
            'description' => $product->short_description,
            'sku' => $product->sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => tenant()->name,
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('product.show', $product->slug),
                'priceCurrency' => tenant()->currency ?? 'USD',
                'price' => $product->price,
                'availability' => $product->inStock() 
                    ? 'https://schema.org/InStock' 
                    : 'https://schema.org/OutOfStock',
            ],
        ];
    }
    
    /**
     * Generate breadcrumb schema
     */
    public function generateBreadcrumbSchema(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }
        
        return [
            '@context' => 'https://schema.org/',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
    
    /**
     * Generate meta tags array for frontend
     */
    public function generateMetaTags(Model $model): array
    {
        return [
            'title' => $model->meta_title ?? $model->name,
            'description' => $model->meta_description,
            'keywords' => $model->meta_keywords,
            'canonical' => $model->canonical_url,
            'og_title' => $model->og_title ?? $model->meta_title ?? $model->name,
            'og_description' => $model->og_description ?? $model->meta_description,
            'og_image' => $model->og_image,
            'twitter_card' => $model->twitter_card ?? 'summary_large_image',
            'robots' => $model->robots_meta ?? 'index,follow',
        ];
    }
}
```

### API Endpoints

**SEO Endpoints**:
- `GET /api/v1/seo/product/{slug}` - Get product SEO data
- `GET /api/v1/seo/category/{slug}` - Get category SEO data
- `GET /api/v1/sitemap` - Get sitemap data (JSON for frontend)
- `GET /sitemap.xml` - Public XML sitemap
- `GET /robots.txt` - Public robots.txt

## Best Practices

### 1. Meta Title Guidelines
- **Length**: 50-60 characters (Google displays ~60)
- **Format**: `Product Name | Category | Store Name`
- **Include**: Primary keyword near the beginning
- **Avoid**: Keyword stuffing, ALL CAPS, special characters

**Good Examples**:
```
Premium Laptop Pro - Gaming & Work | Electronics | My Store
Women's Running Shoes - Size 7 | Athletic | Sports Store
```

### 2. Meta Description Guidelines
- **Length**: 150-160 characters (Google displays ~160)
- **Include**: Call-to-action, key features, benefits
- **Format**: Natural, compelling copy
- **Avoid**: Duplicate descriptions, keyword stuffing

**Good Examples**:
```
Shop Premium Laptop Pro with Intel i7, 16GB RAM, and 1TB SSD. Fast shipping, 30-day returns. Order now and save 20%!

Comfortable women's running shoes with cushioned sole. Available in multiple colors. Free shipping on orders over $50.
```

### 3. URL Slug Guidelines
- **Format**: lowercase, hyphen-separated
- **Length**: Short and descriptive (3-5 words)
- **Include**: Primary keyword
- **Avoid**: Stop words (the, a, an), numbers, special characters

**Good Examples**:
```
/products/premium-laptop-pro
/categories/womens-running-shoes
/collections/summer-sale
```

### 4. Image Alt Text Guidelines
- **Purpose**: Accessibility + SEO
- **Format**: Descriptive, natural language
- **Length**: Under 125 characters
- **Include**: Product name, key features

**Good Examples**:
```
Premium Laptop Pro with 15-inch display and backlit keyboard
Women's blue running shoes with white sole
```

### 5. Structured Data Guidelines
- **Use**: Schema.org vocabulary with JSON-LD format
- **Include**: For products, breadcrumbs, organization, reviews
- **Validate**: Google Rich Results Test
- **Update**: When product data changes

## Common SEO Mistakes to Avoid

❌ **Missing meta tags** - Every public page needs title and description
❌ **Duplicate content** - Each product/category needs unique meta data
❌ **Poor slug structure** - URLs should be readable and keyword-rich
❌ **No alt text** - All product images need descriptive alt text
❌ **Missing structured data** - Products need Schema.org markup
❌ **Broken canonical URLs** - Canonical should point to primary version
❌ **No sitemap** - Search engines need sitemap.xml to discover content
❌ **Blocking crawlers** - Check robots.txt doesn't block important pages
❌ **Slow page load** - Optimize images, enable caching
❌ **Not mobile-friendly** - Ensure responsive design

## Frontend Implementation Checklist

### Next.js Storefront (SSG)

```tsx
// app/products/[slug]/page.tsx
export async function generateMetadata({ params }) {
  const product = await getProduct(params.slug);
  
  return {
    title: product.seo.meta_title,
    description: product.seo.meta_description,
    keywords: product.seo.meta_keywords,
    openGraph: {
      title: product.seo.og_title,
      description: product.seo.og_description,
      images: [product.seo.og_image],
      type: 'product',
    },
    twitter: {
      card: product.seo.twitter_card,
      title: product.seo.og_title,
      description: product.seo.og_description,
      images: [product.seo.og_image],
    },
    robots: product.seo.robots_meta,
    alternates: {
      canonical: product.seo.canonical_url,
    },
  };
}

export default function ProductPage({ params }) {
  const product = await getProduct(params.slug);
  
  return (
    <>
      {/* Structured Data */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: JSON.stringify(product.seo.schema_markup),
        }}
      />
      
      {/* Product Content */}
      <h1>{product.name}</h1>
      <img src={product.image} alt={product.image_alt} />
      {/* ... */}
    </>
  );
}
```

## Testing SEO Implementation

### Tools
- **Google Search Console** - Monitor search performance
- **Google Rich Results Test** - Validate structured data
- **PageSpeed Insights** - Check page performance
- **Screaming Frog** - Crawl site for SEO issues
- **Lighthouse** - Audit SEO, performance, accessibility

### Checklist
- [ ] All products have unique meta titles
- [ ] All products have unique meta descriptions
- [ ] All product images have alt text
- [ ] Product pages have Schema.org markup
- [ ] Categories have proper meta tags
- [ ] Breadcrumbs work correctly
- [ ] Sitemap.xml is accessible
- [ ] Robots.txt is configured
- [ ] Canonical URLs are set correctly
- [ ] Open Graph tags work (test with Facebook debugger)
- [ ] Twitter Cards work (test with Twitter validator)
- [ ] Page speed is acceptable (<3s load time)
- [ ] Mobile-friendly (responsive design)

---

**Remember**: SEO is an ongoing process. Monitor performance, test regularly, and update content to improve rankings.
