# SEO Implementation Guide

## Overview

This document outlines the comprehensive SEO strategy for the multi-tenant e-commerce platform. SEO is critical for organic traffic and must be built into every public-facing content model from the start.

**Business Impact**: Proper SEO implementation can increase organic traffic by 200-500% within 6-12 months, reducing customer acquisition costs and improving ROI.

## Table of Contents

1. [SEO Architecture](#seo-architecture)
2. [Database Schema](#database-schema)
3. [Meta Tags Strategy](#meta-tags-strategy)
4. [Structured Data (Schema.org)](#structured-data)
5. [URL Structure](#url-structure)
6. [Sitemap Generation](#sitemap-generation)
7. [Open Graph & Social Media](#open-graph--social-media)
8. [Performance Optimization](#performance-optimization)
9. [Multi-Tenant SEO Considerations](#multi-tenant-seo-considerations)
10. [Implementation Roadmap](#implementation-roadmap)

---

## SEO Architecture

### Core Principles

1. **Content First**: Every public content type (products, categories, pages) must have comprehensive SEO metadata
2. **Performance Matters**: Fast load times (sub-3s) are critical for SEO rankings
3. **Mobile First**: Google uses mobile-first indexing
4. **Structured Data**: Rich snippets improve CTR by 20-30%
5. **Unique Content**: No duplicate meta tags or descriptions across products/categories

### SEO Layers

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend (Next.js)                    │
│  - Server-Side Generation (SSG)                         │
│  - Meta tag injection                                   │
│  - Structured data rendering                            │
│  - Sitemap.xml serving                                  │
└─────────────────────────────────────────────────────────┘
                           ↕
┌─────────────────────────────────────────────────────────┐
│                    Backend API (Laravel)                 │
│  - SEO data storage (products, categories)              │
│  - SEO service (schema generation)                      │
│  - Sitemap data API                                     │
│  - Store-level SEO configuration                        │
└─────────────────────────────────────────────────────────┘
                           ↕
┌─────────────────────────────────────────────────────────┐
│                    Database (MySQL)                      │
│  - SEO fields on all content tables                     │
│  - Multi-tenant isolation (store_id)                    │
│  - Indexed slug fields for fast lookups                 │
└─────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Products Table Enhancement

**Add to existing `products` migration**:

```php
Schema::table('products', function (Blueprint $table) {
    // Advanced Meta Tags
    $table->string('meta_keywords', 255)->nullable()->after('meta_description');
    $table->string('canonical_url', 512)->nullable()->after('meta_keywords');
    
    // Open Graph Tags
    $table->string('og_title', 100)->nullable()->after('canonical_url');
    $table->text('og_description')->nullable()->after('og_title');
    $table->string('og_image', 512)->nullable()->after('og_description');
    
    // Twitter Card
    $table->enum('twitter_card', ['summary', 'summary_large_image'])
        ->default('summary_large_image')
        ->after('og_image');
    
    // Structured Data
    $table->json('schema_markup')->nullable()->after('twitter_card');
    
    // Robots Meta
    $table->string('robots_meta', 50)->default('index,follow')->after('schema_markup');
    
    // Indexes for SEO
    $table->index(['store_id', 'slug', 'status']); // Fast lookup for public products
});
```

### Categories Table Enhancement

**Add to existing `categories` migration**:

```php
Schema::table('categories', function (Blueprint $table) {
    // Basic Meta Tags
    $table->string('meta_title', 100)->nullable()->after('description');
    $table->text('meta_description')->nullable()->after('meta_title');
    $table->string('meta_keywords', 255)->nullable()->after('meta_description');
    $table->string('canonical_url', 512)->nullable()->after('meta_keywords');
    
    // Open Graph Tags
    $table->string('og_title', 100)->nullable()->after('canonical_url');
    $table->text('og_description')->nullable()->after('og_title');
    $table->string('og_image', 512)->nullable()->after('og_description');
    
    // Breadcrumb Schema
    $table->json('breadcrumb_schema')->nullable()->after('og_image');
    
    // Robots Meta
    $table->string('robots_meta', 50)->default('index,follow')->after('breadcrumb_schema');
    
    // Indexes for SEO
    $table->index(['store_id', 'slug', 'status']); // Fast lookup for public categories
});
```

### Stores Table Enhancement

**Add to `settings` JSON column**:

```json
{
  "seo": {
    "site_name": "My Store",
    "tagline": "Best Products Online",
    "default_meta_title": "{{ store_name }} - Shop Quality Products",
    "default_meta_description": "Shop the best products at {{ store_name }}. Fast shipping, great prices, and excellent customer service.",
    "home_page_title": "Welcome to {{ store_name }}",
    "home_page_description": "Discover amazing products...",
    
    "tracking": {
      "google_analytics_id": "G-XXXXXXXXXX",
      "google_tag_manager_id": "GTM-XXXXXXX",
      "facebook_pixel_id": "1234567890",
      "google_site_verification": "verification_code"
    },
    
    "social": {
      "twitter_handle": "@mystore",
      "facebook_page": "https://facebook.com/mystore",
      "instagram_handle": "@mystore",
      "default_og_image": "https://cdn.example.com/og-default.jpg"
    },
    
    "sitemap": {
      "enabled": true,
      "change_freq": "daily",
      "priority_home": "1.0",
      "priority_categories": "0.8",
      "priority_products": "0.6",
      "priority_pages": "0.5"
    },
    
    "robots_txt": "User-agent: *\nAllow: /\nSitemap: {{ site_url }}/sitemap.xml"
  }
}
```

---

## Meta Tags Strategy

### Title Tag Best Practices

**Format**: `[Product Name] | [Category] | [Store Name]`

**Guidelines**:
- Length: 50-60 characters (Google displays ~60)
- Include primary keyword near the beginning
- Make it compelling for users to click
- Unique for every product/category

**Examples**:
```
✅ Premium Laptop Pro 15" | Laptops | Tech Store
✅ Women's Running Shoes Size 7 | Athletic | Sports Co
✅ Organic Green Tea 100g | Beverages | Health Market

❌ Buy Now! Best Laptop Ever!!! (too salesy, no brand)
❌ Product #12345 | Store (not descriptive)
❌ This is the most amazing laptop you've ever seen (too long, no keywords)
```

### Meta Description Best Practices

**Format**: `[Feature 1], [Feature 2]. [Call-to-action]. [Unique selling point].`

**Guidelines**:
- Length: 150-160 characters (Google displays ~160)
- Include call-to-action (Buy, Shop, Discover)
- Highlight key features and benefits
- Unique for every product/category
- Natural, compelling copy (not keyword stuffing)

**Examples**:
```
✅ Shop Premium Laptop Pro with Intel i7, 16GB RAM, 1TB SSD. Fast shipping, 30-day returns. Save 20% today!
(155 chars, has CTA, features, benefits)

✅ Comfortable women's running shoes with cushioned sole. Available in 10 colors. Free shipping over $50.
(106 chars, descriptive, includes offer)

❌ laptop computer buy now cheap discount sale best quality (keyword stuffing)
❌ This product is available in our store. (not descriptive)
❌ Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor... (too generic)
```

### Meta Keywords

**Status**: Less important than before, but still used by some search engines

**Guidelines**:
- 5-10 keywords max
- Comma-separated
- Include variations and synonyms
- Don't repeat keywords

**Example**:
```
laptop, gaming laptop, premium laptop, intel i7 laptop, 15 inch laptop, work computer
```

---

## Structured Data (Schema.org)

### Product Schema

**Type**: `https://schema.org/Product`

**Required Fields**:
- `name` - Product name
- `image` - Product image URL
- `description` - Short description
- `sku` - Product SKU
- `brand` - Brand or store name
- `offers` - Price, currency, availability

**Full Example**:
```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Premium Laptop Pro",
  "image": [
    "https://example.com/images/laptop-1.jpg",
    "https://example.com/images/laptop-2.jpg",
    "https://example.com/images/laptop-3.jpg"
  ],
  "description": "High-performance laptop with Intel i7 processor, 16GB RAM, and 1TB SSD storage. Perfect for gaming and professional work.",
  "sku": "LAP-PRO-001",
  "mpn": "925872",
  "brand": {
    "@type": "Brand",
    "name": "TechBrand"
  },
  "offers": {
    "@type": "Offer",
    "url": "https://example.com/products/premium-laptop-pro",
    "priceCurrency": "USD",
    "price": "999.99",
    "priceValidUntil": "2026-12-31",
    "availability": "https://schema.org/InStock",
    "itemCondition": "https://schema.org/NewCondition",
    "seller": {
      "@type": "Organization",
      "name": "Tech Store"
    }
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.5",
    "reviewCount": "24"
  },
  "review": [
    {
      "@type": "Review",
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "5"
      },
      "author": {
        "@type": "Person",
        "name": "John Doe"
      },
      "reviewBody": "Excellent laptop! Fast and reliable."
    }
  ]
}
```

**Benefits**:
- Rich snippets in search results (price, availability, ratings)
- Improved CTR (20-30% increase)
- Better product visibility in Google Shopping

### Breadcrumb Schema

**Type**: `https://schema.org/BreadcrumbList`

**Example**:
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
    },
    {
      "@type": "ListItem",
      "position": 4,
      "name": "Premium Laptop Pro",
      "item": "https://example.com/products/premium-laptop-pro"
    }
  ]
}
```

### Organization Schema

**Type**: `https://schema.org/Organization`

**Use**: On homepage and about page

**Example**:
```json
{
  "@context": "https://schema.org/",
  "@type": "Organization",
  "name": "Tech Store",
  "url": "https://techstore.com",
  "logo": "https://techstore.com/logo.png",
  "sameAs": [
    "https://www.facebook.com/techstore",
    "https://www.twitter.com/techstore",
    "https://www.instagram.com/techstore"
  ],
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+1-555-123-4567",
    "contactType": "Customer Service",
    "areaServed": "US",
    "availableLanguage": "English"
  }
}
```

---

## URL Structure

### Best Practices

**URLs should be**:
- Lowercase
- Hyphen-separated (not underscores)
- Short and descriptive (3-5 words)
- Include primary keyword
- Avoid stop words (the, a, an, of)
- Human-readable

### Product URLs

**Format**: `/products/{slug}`

**Good Examples**:
```
✅ /products/premium-laptop-pro
✅ /products/womens-running-shoes-blue
✅ /products/organic-green-tea-100g
```

**Bad Examples**:
```
❌ /products/12345 (no keywords)
❌ /products/the-best-laptop-ever (stop words)
❌ /products/Premium_Laptop_PRO (underscores, mixed case)
❌ /product.php?id=12345 (old-style dynamic URL)
```

### Category URLs

**Format**: `/categories/{parent-slug}/{slug}` or `/categories/{slug}`

**Good Examples**:
```
✅ /categories/electronics
✅ /categories/electronics/laptops
✅ /categories/clothing/womens/shoes
```

### Collection URLs

**Format**: `/collections/{slug}`

**Good Examples**:
```
✅ /collections/summer-sale
✅ /collections/new-arrivals
✅ /collections/best-sellers
```

---

## Sitemap Generation

### XML Sitemap Structure

**Main Sitemap** (`/sitemap.xml`):
```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <sitemap>
    <loc>https://example.com/sitemap-products.xml</loc>
    <lastmod>2026-04-06T10:00:00+00:00</lastmod>
  </sitemap>
  <sitemap>
    <loc>https://example.com/sitemap-categories.xml</loc>
    <lastmod>2026-04-06T10:00:00+00:00</lastmod>
  </sitemap>
  <sitemap>
    <loc>https://example.com/sitemap-pages.xml</loc>
    <lastmod>2026-04-06T10:00:00+00:00</lastmod>
  </sitemap>
</sitemapindex>
```

**Product Sitemap** (`/sitemap-products.xml`):
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
  <url>
    <loc>https://example.com/products/premium-laptop-pro</loc>
    <lastmod>2026-04-06</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
    <image:image>
      <image:loc>https://example.com/images/laptop-1.jpg</image:loc>
      <image:title>Premium Laptop Pro</image:title>
    </image:image>
  </url>
</urlset>
```

### API Endpoints for Sitemap

```php
// routes/web.php (public routes)
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-products.xml', [SitemapController::class, 'products'])->name('sitemap.products');
Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories'])->name('sitemap.categories');
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
```

### Sitemap Generation Frequency

- **Products**: Daily (if inventory changes frequently)
- **Categories**: Weekly (structure changes less often)
- **Pages**: On-demand (when content is updated)
- **Cache**: Cache sitemaps for 24 hours to reduce load

---

## Open Graph & Social Media

### Open Graph Tags

**Purpose**: Control how content appears when shared on Facebook, LinkedIn

**Required Tags**:
```html
<meta property="og:type" content="product" />
<meta property="og:title" content="Premium Laptop Pro" />
<meta property="og:description" content="High-performance laptop..." />
<meta property="og:image" content="https://example.com/images/laptop-og.jpg" />
<meta property="og:url" content="https://example.com/products/premium-laptop-pro" />
<meta property="og:site_name" content="Tech Store" />

<!-- Product-specific -->
<meta property="product:price:amount" content="999.99" />
<meta property="product:price:currency" content="USD" />
```

### Twitter Cards

**Purpose**: Control how content appears when shared on Twitter

**Types**:
- `summary` - Small image (120x120)
- `summary_large_image` - Large image (800x418) - **Use this for products**

**Required Tags**:
```html
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:site" content="@techstore" />
<meta name="twitter:title" content="Premium Laptop Pro" />
<meta name="twitter:description" content="High-performance laptop..." />
<meta name="twitter:image" content="https://example.com/images/laptop-twitter.jpg" />
```

### Image Requirements

**Open Graph Image**:
- Size: 1200x630px (1.91:1 ratio)
- Format: JPG or PNG
- Max size: 8MB
- No text overlay (Facebook may reject)

**Twitter Card Image**:
- Size: 800x418px (1.91:1 ratio)
- Format: JPG, PNG, WEBP, GIF
- Max size: 5MB

---

## Performance Optimization

### Core Web Vitals (Google Ranking Factors)

1. **LCP (Largest Contentful Paint)**: < 2.5s
   - Optimize images (WebP format, lazy loading)
   - Use CDN for static assets
   - Server-side rendering (SSG with Next.js)

2. **FID (First Input Delay)**: < 100ms
   - Minimize JavaScript execution
   - Code splitting
   - Defer non-critical scripts

3. **CLS (Cumulative Layout Shift)**: < 0.1
   - Set image dimensions
   - Reserve space for ads/embeds
   - Avoid dynamic content injection

### Image Optimization

**Best Practices**:
- Use WebP format (30% smaller than JPEG)
- Lazy load below-the-fold images
- Responsive images with `srcset`
- Compress images (quality 80-85)
- Use CDN for image delivery

**Example**:
```html
<img 
  src="laptop-800.webp" 
  srcset="laptop-400.webp 400w, laptop-800.webp 800w, laptop-1200.webp 1200w"
  sizes="(max-width: 600px) 400px, (max-width: 1200px) 800px, 1200px"
  alt="Premium Laptop Pro with 15-inch display"
  loading="lazy"
  width="800"
  height="600"
/>
```

### Caching Strategy

**Browser Caching**:
```
# .htaccess or nginx config
# Cache images for 1 year
<IfModule mod_expires.c>
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
</IfModule>
```

**API Response Caching**:
```php
// Cache product data for 1 hour
return Cache::remember("product:{$slug}", 3600, function () use ($slug) {
    return Product::where('slug', $slug)->with('seo')->first();
});
```

---

## Multi-Tenant SEO Considerations

### Domain Strategy

**Option 1: Subdomains** (Recommended)
- `store1.platform.com`
- `store2.platform.com`
- Each store has its own sitemap
- Better for SEO isolation

**Option 2: Custom Domains**
- `store1.com`
- `store2.com`
- Best for brand identity
- Requires SSL for each domain

**Option 3: Subdirectories** (Not Recommended)
- `platform.com/store1`
- `platform.com/store2`
- Difficult to isolate SEO performance

### Sitemap per Store

Each store needs its own sitemap:
- `store1.example.com/sitemap.xml`
- `store2.example.com/sitemap.xml`

**Implementation**:
```php
// SitemapController.php
public function index()
{
    $store = tenant(); // Get current store from middleware
    
    $products = Product::where('store_id', $store->id)
        ->where('status', 'active')
        ->select('slug', 'updated_at')
        ->get();
    
    return response()->view('sitemap.products', compact('products'))
        ->header('Content-Type', 'text/xml');
}
```

### Robots.txt per Store

Each store can have custom robots.txt:

```
# Store 1 robots.txt
User-agent: *
Allow: /

Sitemap: https://store1.example.com/sitemap.xml

# Disallow admin pages
Disallow: /admin/
Disallow: /cart/
Disallow: /checkout/
Disallow: /account/
```

---

## Implementation Roadmap

### Phase 1: Database & Models (Week 1)

- [ ] Add SEO fields to products table
- [ ] Add SEO fields to categories table
- [ ] Update Product model with SEO attributes
- [ ] Update Category model with SEO attributes
- [ ] Add SEO settings to Store model
- [ ] Create migrations
- [ ] Seed sample SEO data

### Phase 2: Service Layer (Week 1-2)

- [ ] Create SeoService
- [ ] Implement `generateProductSchema()` method
- [ ] Implement `generateBreadcrumbSchema()` method
- [ ] Implement `generateMetaTags()` method
- [ ] Implement `generateSitemap()` method
- [ ] Add unit tests for SeoService

### Phase 3: API Layer (Week 2)

- [ ] Add SEO data to ProductController responses
- [ ] Add SEO data to CategoryController responses
- [ ] Create SitemapController
- [ ] Add sitemap routes (sitemap.xml, robots.txt)
- [ ] Update API documentation

### Phase 4: Frontend Integration (Week 3)

- [ ] Implement Next.js `generateMetadata()` for products
- [ ] Implement Next.js `generateMetadata()` for categories
- [ ] Add structured data rendering
- [ ] Add Open Graph tags
- [ ] Add Twitter Card tags
- [ ] Test with Facebook Debugger
- [ ] Test with Twitter Card Validator

### Phase 5: Testing & Validation (Week 4)

- [ ] Test with Google Rich Results Test
- [ ] Run Lighthouse audits (target: 90+ SEO score)
- [ ] Test sitemap.xml accessibility
- [ ] Validate structured data with Google's tool
- [ ] Test page speed (target: <3s load time)
- [ ] Test mobile responsiveness
- [ ] Submit sitemap to Google Search Console

### Phase 6: Monitoring & Optimization (Ongoing)

- [ ] Set up Google Search Console for each store
- [ ] Monitor Core Web Vitals
- [ ] Track organic traffic growth
- [ ] Monitor keyword rankings
- [ ] A/B test meta descriptions
- [ ] Regularly update content for freshness

---

## Testing & Validation Tools

### Google Tools
- **Google Search Console** - Monitor search performance, submit sitemaps
- **Google Rich Results Test** - Validate structured data
- **Google PageSpeed Insights** - Test performance and Core Web Vitals
- **Google Mobile-Friendly Test** - Ensure mobile compatibility

### Third-Party Tools
- **Screaming Frog** - Crawl site for SEO issues (broken links, missing meta, duplicate content)
- **Ahrefs** - Keyword research, backlink analysis
- **SEMrush** - Competitor analysis, keyword tracking
- **Lighthouse** - Automated audits (SEO, performance, accessibility)

### Social Media Validators
- **Facebook Sharing Debugger** - Test Open Graph tags
- **Twitter Card Validator** - Test Twitter Cards
- **LinkedIn Post Inspector** - Test LinkedIn sharing

---

## Common SEO Mistakes & Solutions

| Mistake | Impact | Solution |
|---------|--------|----------|
| Missing meta descriptions | Lower CTR | Add unique descriptions (150-160 chars) |
| Duplicate meta tags | Cannibalization | Generate unique tags per product |
| Slow page load (>3s) | Lower rankings | Optimize images, use CDN, enable caching |
| Missing alt text | Accessibility & SEO | Add descriptive alt text to all images |
| No structured data | No rich snippets | Implement Schema.org Product markup |
| Poor URL structure | Lower rankings | Use keyword-rich, hyphenated slugs |
| No sitemap | Poor indexing | Generate and submit sitemap.xml |
| Blocking crawlers | Not indexed | Review robots.txt, ensure /products is allowed |
| Not mobile-friendly | Lower mobile rankings | Implement responsive design |
| Thin content | Poor rankings | Add detailed descriptions (300+ words) |

---

## Success Metrics

### Track These KPIs

**Traffic**:
- Organic search traffic (month-over-month growth)
- Keyword rankings (target: top 10 for 50+ keywords)
- Click-through rate (CTR) in search results

**Engagement**:
- Bounce rate (target: <50%)
- Pages per session (target: >3)
- Average session duration (target: >2 minutes)

**Conversions**:
- Organic conversion rate (target: 2-3%)
- Revenue from organic traffic
- Return on SEO investment (ROI)

**Technical**:
- Lighthouse SEO score (target: >90)
- Core Web Vitals (LCP <2.5s, FID <100ms, CLS <0.1)
- Page load time (target: <3s)
- Mobile usability score (target: 100%)

---

## Resources

### Documentation
- [Google SEO Starter Guide](https://developers.google.com/search/docs/beginner/seo-starter-guide)
- [Schema.org Product Documentation](https://schema.org/Product)
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Card Documentation](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)

### Tools
- [Google Search Console](https://search.google.com/search-console)
- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [PageSpeed Insights](https://pagespeed.web.dev/)
- [Screaming Frog SEO Spider](https://www.screamingfrog.co.uk/seo-spider/)

---

**Next Steps**: Begin with Phase 1 - add SEO fields to database and models. Test thoroughly before moving to Phase 2.
