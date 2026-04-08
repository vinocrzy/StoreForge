# E-Commerce Platform Development Guidelines

## Project Overview

Multi-tenant white-label e-commerce platform with shared backend (Laravel), shared admin panel (React), and custom client storefronts (Next.js). Each client gets a unique storefront while reusing core infrastructure.

**Business Model**: Sell custom-branded storefronts ($2K-$10K setup + $49-$499/month recurring). Platform serves multiple clients from single backend with tenant isolation.

## Architecture Principles

### Multi-Tenancy
- **Single database** with application-level isolation via `store_id`
- All models use global scopes: `->where('store_id', tenant()->id)`
- Middleware validates `X-Store-ID` header on all API requests
- Never expose data across tenants - security critical

### Repository Structure
```
platform/              # Git repo 1: Shared backend + admin
  backend/            # Laravel 11 API
  admin-panel/        # React 18 TypeScript SPA
storefront-template/   # Git repo 2: Base template
client-*/             # Git repos 3+: Per-client storefronts
```

See [docs/15-repository-structure.md](docs/15-repository-structure.md) for workflow details.

## Tech Stack

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| Backend | Laravel | 11.x | REST API, multi-tenant logic |
| Admin | React + TypeScript | 19+ | Store management dashboard |
| Admin UI | TailAdmin + Tailwind CSS | 4.0 | Custom design system |
| Storefront | Next.js | 14+ | Client-facing stores (SSG) |
| Database | MySQL/PostgreSQL | 8.0+/14+ | Multi-tenant data |
| Cache | Redis | 7+ | Sessions, cache, queues |
| Auth | Laravel Sanctum | - | Token-based API auth |
| State | Redux Toolkit | 2.11 | Admin panel state |

## Code Standards

### Backend (Laravel)

**Authentication**: Phone-first strategy
```php
// ✅ Good - Accept phone or email, prioritize phone
$request->validate([
    'login' => 'required|string',  // Phone (+12025551234) or email
    'password' => 'required|string',
]);

$loginField = preg_match('/^[\d\s\-\+\(\)]+$/', $request->login) ? 'phone' : 'email';
$user = User::where($loginField, $request->login)->first();

// ❌ Bad - Email-only login (phone is primary method)
$request->validate([
    'email' => 'required|email',
    'password' => 'required',
]);
```

**CRITICAL**: All users and customers MUST have phone numbers:
- Phone is **required** field (NOT NULL)
- Phone is **primary** authentication method
- Phone in E.164 format: `+12025551234`
- Phone unique per store (tenant isolation)

See [docs/18-phone-authentication-strategy.md](docs/18-phone-authentication-strategy.md) for complete implementation.

**Service Pattern**: Controllers delegate to services
```php
// ✅ Good
public function store(ProductRequest $request) {
    return $this->productService->create($request->validated());
}

// ❌ Bad - business logic in controller
public function store(ProductRequest $request) {
    $product = Product::create($request->all());
    $product->categories()->sync($request->categories);
    event(new ProductCreated($product));
    return $product;
}
```

**Tenant Isolation**: Always use global scopes
```php
// ✅ Good - automatic tenant scoping
protected static function booted() {
    static::addGlobalScope('store', function (Builder $builder) {
        if (tenant()->exists()) {
            $builder->where('store_id', tenant()->id);
        }
    });
}

// ❌ Bad - manual scoping (error-prone)
$products = Product::where('store_id', $storeId)->get();
```

**API Controllers**: Must be documented
```php
/**
 * @group Products
 * 
 * List products
 * 
 * @queryParam search string Search term. Example: laptop
 * @response 200 {"data": [...]}
 */
public function index(Request $request) { }
```

See [docs/API-DOCS-QUICK-REFERENCE.md](docs/API-DOCS-QUICK-REFERENCE.md) for templates.

### Frontend (React Admin Panel)

**Admin Panel Stack**: React 19 + TypeScript 6 + Vite 8
- **UI Library**: TailAdmin (custom components with Tailwind CSS 4)
- **Design System**: See [docs/19-admin-panel-design-system.md](docs/19-admin-panel-design-system.md)
- **State Management**: Redux Toolkit 2 + RTK Query
- **Routing**: React Router 7 with protected routes
- **HTTP Client**: Axios with auto-auth headers

**TypeScript Always**: No `.jsx` files, use `.tsx` with strict mode

**Type-Only Imports**: Use `type` keyword for type imports (required by `verbatimModuleSyntax`)
```typescript
// ✅ Good - type-only imports
import { type AxiosInstance, type AxiosResponse } from 'axios';
import { type PayloadAction } from '@reduxjs/toolkit';
import type { User, Store } from '../types/auth';

// ❌ Bad - will cause TS build errors
import { AxiosInstance, PayloadAction } from 'axios';
```

**Component Structure**:
```typescript
// ✅ Good - typed props, RTK Query, TailAdmin components
interface ProductListProps {
  storeId: number;
  status?: 'active' | 'draft';
}

export const ProductList: React.FC<ProductListProps> = ({ storeId, status }) => {
  const { data, isLoading, error } = useGetProductsQuery({ storeId, status });
  
  if (isLoading) return <div className="p-6 text-center">Loading...</div>;
  if (error) return <Alert type="error">Error loading products</Alert>;
  
  return <Table data={data} columns={columns} />;
};

// ❌ Bad - no types, unclear structure, manual fetch
export default function ProductList(props) {
  const [data, setData] = useState([]);
  useEffect(() => {
    fetch('/api/products').then(r => r.json()).then(setData);
  }, []);
  return <Table dataSource={data} />;
}
```

**State Management**: Use RTK Query for API calls, Redux for global state
```typescript
// ✅ Good - RTK Query for API data
import { useGetProductsQuery, useUpdateProductMutation } from '../services/products';

const { data: products, isLoading } = useGetProductsQuery({ page: 1 });
const [updateProduct] = useUpdateProductMutation();

// ✅ Good - Redux slice for auth state
import { useAppSelector } from '../store/hooks';
const { user, currentStore } = useAppSelector((state) => state.auth);

// ❌ Bad - manual fetch with useState
const [products, setProducts] = useState([]);
useEffect(() => {
  fetch('/api/products').then(r => r.json()).then(setProducts);
}, []);
```

**API Integration**: Use centralized API client
```typescript
// services/apiClient.ts - Auto-injects auth headers
import { apiClient } from '../services/apiClient';

// Headers automatically added:
// - Authorization: Bearer {token}
// - X-Store-ID: {store_id}

const response = await apiClient.get<ProductsResponse>('/products');
```

**Protected Routes**: Wrap authenticated pages
```typescript
// ✅ Good - protected route wrapper
<Route
  path="/"
  element={
    <ProtectedRoute>
      <MainLayout />
    </ProtectedRoute>
  }
>
  <Route index element={<DashboardPage />} />
  <Route path="products" element={<ProductsPage />} />
</Route>

// ❌ Bad - no authentication guard
<Route path="/" element={<DashboardPage />} />
```

**TailAdmin Components**: Custom UI components with Tailwind CSS
```typescript
// ✅ Good - TailAdmin components
import { Table } from '../components/ui/table';
import { Button } from '../components/ui/button/Button';
import { Alert } from '../components/ui/alert/Alert';
import { Modal } from '../components/ui/modal';

const [alert, setAlert] = useState<{type: 'success' | 'error', message: string} | null>(null);

const onFinish = async (values) => {
  try {
    await createProduct(values).unwrap();
    setAlert({ type: 'success', message: 'Product created!' });
  } catch (error) {
    setAlert({ type: 'error', message: 'Failed to create product' });
  }
};

return (
  <form onSubmit={handleSubmit} className="space-y-6">
    {alert && <Alert type={alert.type}>{alert.message}</Alert>}
    
    <div>
      <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
        Product Name
      </label>
      <input
        type="text"
        className="w-full rounded-lg border border-stroke bg-white py-3 px-4.5 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
      />
    </div>
    <Button variant="primary" type="submit">Save</Button>
  </form>
);
```

**React 19 Notes**: No longer need to import React for JSX
```typescript
// ✅ Good - React 19 style (no React import needed)
import { useState } from 'react';
import { Button } from '../components/ui/button/Button';

export const MyComponent = () => {
  const [count, setCount] = useState(0);
  return <Button onClick={() => setCount(count + 1)}>{count}</Button>;
};

// ❌ Old style - unnecessary in React 19
import React, { useState } from 'react';
```
```

### Storefront (Next.js)

**Static Generation**: Use `getStaticProps` for pages
```typescript
// ✅ Good - SSG for SEO
export async function getStaticProps() {
  const products = await fetchProducts();
  return { props: { products }, revalidate: 3600 };
}

// ❌ Bad - client-side only (poor SEO)
export default function Products() {
  const [products, setProducts] = useState([]);
  useEffect(() => { fetchProducts().then(setProducts); }, []);
}
```

## Testing Requirements

### Backend Tests
```bash
# Feature tests for all API endpoints
php artisan test --filter=Api

# Must include:
# - Authentication tests
# - Tenant isolation tests (critical!)
# - Permission tests
# - Validation tests
```

**Tenant Isolation Test Example**:
```php
public function test_products_are_scoped_to_tenant() {
    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();
    
    $product1 = Product::factory()->create(['store_id' => $store1->id]);
    $product2 = Product::factory()->create(['store_id' => $store2->id]);
    
    $this->actingAs($store1->user)
        ->getJson('/api/v1/products')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $product1->id);
}
```

### Frontend Tests
```bash
# Component tests with React Testing Library
npm test

# E2E tests for critical flows
npm run test:e2e
```

## Database Conventions

**All tables need**:
- `id` (primary key)
- `store_id` (foreign key to stores) - for multi-tenant tables
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes where appropriate)

**Indexes**:
```sql
-- Always composite index with store_id first
INDEX idx_store_product (store_id, id)
INDEX idx_store_sku (store_id, sku)
```

**Migrations**: Never edit existing migrations, create new ones
```bash
# ✅ Good
php artisan make:migration add_status_to_products_table

# ❌ Bad - editing old migration breaks production
```

## SEO Requirements (CRITICAL)

**SEO is critical for e-commerce success**. Every public-facing content model MUST include comprehensive SEO metadata.

**All products and categories need**:
- ✅ `slug` - URL-friendly identifier (unique per store)
- ✅ `meta_title` - SEO title (50-60 characters)
- ✅ `meta_description` - SEO description (150-160 characters)
- ✅ `meta_keywords` - Optional keywords (comma-separated)
- ✅ `canonical_url` - Optional canonical URL override
- ✅ `og_title` - Open Graph title (for Facebook/LinkedIn sharing)
- ✅ `og_description` - Open Graph description
- ✅ `og_image` - Open Graph image URL (1200x630px)
- ✅ `twitter_card` - Twitter card type (summary_large_image)
- ✅ `schema_markup` - JSON-LD structured data (Schema.org)
- ✅ `robots_meta` - Robots directive (index,follow)

**Product SEO Example**:
```php
// Database fields
$table->string('slug')->unique();
$table->string('meta_title', 100)->nullable();
$table->text('meta_description')->nullable();
$table->string('meta_keywords')->nullable();
$table->json('schema_markup')->nullable(); // Product Schema.org

// API Response - Include SEO data
public function show($slug) {
    $product = Product::where('slug', $slug)->first();
    
    return [
        'data' => $product,
        'seo' => [
            'meta_title' => $product->meta_title ?? "{$product->name} | Store",
            'meta_description' => $product->meta_description,
            'schema_markup' => $this->seoService->generateProductSchema($product),
            'og_image' => $product->og_image ?? $product->primaryImage?->url,
            'breadcrumbs' => $this->seoService->getBreadcrumbs($product),
        ],
    ];
}
```

**Schema.org Structured Data** (required for products):
```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Premium Laptop Pro",
  "image": "https://example.com/laptop.jpg",
  "description": "High-performance laptop...",
  "sku": "LAP-001",
  "brand": {"@type": "Brand", "name": "StoreName"},
  "offers": {
    "@type": "Offer",
    "price": "999.99",
    "priceCurrency": "USD",
    "availability": "https://schema.org/InStock"
  }
}
```

**Sitemap Generation**:
- All stores need `sitemap.xml` and `robots.txt`
- Update sitemap daily for products, weekly for categories
- Submit to Google Search Console

**Frontend (Next.js) Requirements**:
```typescript
// Use generateMetadata for SEO
export async function generateMetadata({ params }) {
  const product = await getProduct(params.slug);
  
  return {
    title: product.seo.meta_title,
    description: product.seo.meta_description,
    openGraph: {
      title: product.seo.og_title,
      description: product.seo.og_description,
      images: [product.seo.og_image],
    },
  };
}
```

See [docs/17-seo-implementation.md](docs/17-seo-implementation.md) for complete SEO strategy and [.github/skills/ecommerce-seo/SKILL.md](.github/skills/ecommerce-seo/SKILL.md) for implementation patterns.

## API Design

**REST conventions**:
- `GET /v1/products` - List (paginated)
- `GET /v1/products/{id}` - Show one
- `POST /v1/products` - Create
- `PUT /v1/products/{id}` - Update (full)
- `PATCH /v1/products/{id}` - Update (partial)
- `DELETE /v1/products/{id}` - Delete (soft)

**Required headers**:
```
Authorization: Bearer {token}
X-Store-ID: {store_id}
Accept: application/json
Content-Type: application/json
```

**Response format**:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100
  },
  "links": {
    "first": "...",
    "last": "...",
    "next": "...",
    "prev": null
  }
}
```

**Error format**:
```json
{
  "message": "Validation failed",
  "errors": {
    "field": ["Error message"]
  }
}
```

## Documentation Requirements

### API Endpoints
**Every API controller must be documented with Scribe annotations**. After creating/updating controllers:
```bash
php artisan scribe:generate
```

**CRITICAL - API Documentation Workflow**: When creating or updating API endpoints, you MUST update:
1. **Scribe annotations** in controller (for interactive docs)
2. **[docs/API-REFERENCE.md](docs/API-REFERENCE.md)** - Add/update endpoint with request/response examples
3. **[.github/skills/ecommerce-api-integration/SKILL.md](.github/skills/ecommerce-api-integration/SKILL.md)** - Update endpoint list and examples
4. **Regenerate Scribe docs**: `php artisan scribe:generate`

See [docs/API-DOCUMENTATION-WORKFLOW.md](docs/API-DOCUMENTATION-WORKFLOW.md) for complete checklist and workflow.
See [docs/16-api-documentation-system.md](docs/16-api-documentation-system.md) for Scribe annotation guide.

### Code Comments
- PHPDoc for all public methods
- Inline comments for complex business logic
- No obvious comments (`$i++; // increment i`)

### Architecture Docs
Major changes require updating:
- [docs/01-system-architecture.md](docs/01-system-architecture.md) - System design
- [docs/02-backend-architecture.md](docs/02-backend-architecture.md) - Laravel structure
- [docs/03-database-schema.md](docs/03-database-schema.md) - Database changes

## Security Requirements

**Never commit**:
- `.env` files
- API keys or secrets
- Customer data

**Authentication**:
- All API endpoints require authentication except public storefront
- Use Laravel Sanctum tokens
- Implement rate limiting (60/minute authenticated, 10/minute guest)

**Input Validation**:
- Use Form Requests for all POST/PUT/PATCH
- Sanitize user input
- Validate file uploads

**Tenant Isolation** (CRITICAL):
- Every multi-tenant query MUST include `store_id` check
- Never trust client-provided `store_id` - get from authenticated context
- Test tenant isolation for every new feature

## Development Workflow

### Starting Work
```bash
# Backend
cd platform/backend
composer install
php artisan migrate
php artisan serve

# Admin
cd platform/admin-panel
npm install
npm run dev

# Storefront
cd storefront-template
npm install
npm run dev
```

### Before Committing
```bash
# Backend
php artisan test
php artisan scribe:generate  # Update API docs
./vendor/bin/phpstan analyse # Static analysis

# Admin Panel (CRITICAL - Always check types!)
cd platform/admin-panel
npm run build                # TypeScript type check + build
npm run lint                 # ESLint check
# Fix any type errors before committing!

# Storefront (when implemented)
npm test
npm run lint
npm run type-check

# CRITICAL: Update PROGRESS.md
# 1. Mark completed tasks with [x]
# 2. Update phase completion percentages
# 3. Add deliverables summary
# 4. Commit PROGRESS.md with descriptive message
git add PROGRESS.md
git commit -m "docs: Update PROGRESS.md - [describe milestone]"
```

**Admin Panel TypeScript Rules**:
- Always use type-only imports: `import { type AxiosInstance } from 'axios'`
- No unused imports (React 19 doesn't need React import for JSX)
- Fix all type errors before committing
- Run `npm run build` to verify no type errors

### Creating Client Storefront
```bash
scripts/create-client-store.bat "Client Name" store_id
```

### Progress Tracking & Documentation (CRITICAL)

**ALWAYS update PROGRESS.md after completing significant work**. This is not optional - it's a critical habit for tracking development progress.

**When to update PROGRESS.md**:
- ✅ After completing a major task or milestone
- ✅ After finishing a database migration batch
- ✅ After implementing a complete feature (models + services + controllers)
- ✅ After completing a phase or sub-phase
- ✅ After seeding test data
- ✅ At the end of each work session (daily summary)
- ✅ Before committing significant changes

**What to update**:
1. **Task Status**: Mark completed tasks with `[x]` checkboxes
2. **Phase Progress**: Update completion percentages (e.g., "50% Complete")
3. **Deliverables**: List what was actually completed with specifics
4. **Current Status**: Update the current phase status (🚧 IN PROGRESS, ✅ COMPLETE, ⏳ PENDING)
5. **Summary Section**: Add progress summary showing what's done vs. what remains

**How to update**:
```bash
# After completing work
1. Edit PROGRESS.md with detailed updates
2. Update task checkboxes: [ ] → [x]
3. Update percentages: "0%" → "50%" → "100%"
4. Add completion dates for finished phases
5. Commit with descriptive message

# Example commit message
git add PROGRESS.md
git commit -m "docs: Update PROGRESS.md - Phase 2 Product Catalog 60% complete"
```

**Progress Update Template**:
```markdown
## Phase X: [Phase Name] 🚧 IN PROGRESS

**Status**: 🚧 XX% Complete
**Started**: [Date]
**Completed**: [Date or N/A]

### Tasks Breakdown

#### X.X [Feature Name] ✅ COMPLETE (or 🚧 IN PROGRESS)
- [x] Task 1 (completed)
- [x] Task 2 (completed)
- [ ] Task 3 (pending)

**Deliverables**:
- ✅ Item 1 with specific details
- ✅ Item 2 with metrics (e.g., "90 products seeded")
- 🚧 Item 3 in progress

**Overall Phase X Status**: 🚧 XX% Complete
```

**Example of good progress tracking**:
```markdown
**Completed**:
- ✅ Product catalog database schema (5 migrations)
- ✅ 4 models with tenant scoping
- ✅ Service layer (ProductService, CategoryService)
- ✅ Seeded data: 84 categories, 90 products, 228 images

**In Progress**:
- 🚧 API controllers (created, need implementation)

**Pending**:
- ⏳ API documentation
- ⏳ Tests
```

**Why this matters**:
- 📊 Clear visibility into project progress
- 🎯 Easy to resume work after breaks
- 📝 Documentation for stakeholders
- ✅ Prevents forgetting completed work
- 🔄 Shows incremental progress towards goals

**Emoji Guide**:
- ✅ COMPLETE - Task is 100% done
- 🚧 IN PROGRESS - Currently working on it
- ⏳ PENDING/NOT STARTED - Scheduled but not started
- ❌ BLOCKED - Cannot proceed (with reason)

## Key Documentation

### Getting Started
- [docs/11-getting-started.md](docs/11-getting-started.md) - Setup guide
- [docs/TEST-ACCOUNTS.md](docs/TEST-ACCOUNTS.md) - **Test accounts for development (13 admin users, 45 customers)**
- [docs/13-implementation-priority.md](docs/13-implementation-priority.md) - What to build first

### Architecture
- [docs/01-system-architecture.md](docs/01-system-architecture.md) - High-level design
- [docs/07-multi-tenancy.md](docs/07-multi-tenancy.md) - Tenant isolation strategy
- [docs/15-repository-structure.md](docs/15-repository-structure.md) - Multi-repo workflow

### Implementation
- [docs/02-backend-architecture.md](docs/02-backend-architecture.md) - Laravel patterns
- [docs/03-database-schema.md](docs/03-database-schema.md) - Complete schema
- [docs/04-api-design.md](docs/04-api-design.md) - API specifications
- [docs/19-admin-panel-design-system.md](docs/19-admin-panel-design-system.md) - **TailAdmin design system & UI components**
- [docs/API-REFERENCE.md](docs/API-REFERENCE.md) - **Complete API endpoint reference (60 endpoints)**
- [docs/API-DOCUMENTATION-WORKFLOW.md](docs/API-DOCUMENTATION-WORKFLOW.md) - **API documentation update workflow (CRITICAL)**
- [docs/16-api-documentation-system.md](docs/16-api-documentation-system.md) - API docs setup
- [docs/17-seo-implementation.md](docs/17-seo-implementation.md) - SEO strategy & best practices
- [.github/skills/ecommerce-api-integration/SKILL.md](.github/skills/ecommerce-api-integration/SKILL.md) - **Copilot skill for API integration**
- [.github/skills/ecommerce-admin-ui/SKILL.md](.github/skills/ecommerce-admin-ui/SKILL.md) - **Copilot skill for admin panel UI development**
- [.github/skills/honey-bee-storefront-design/SKILL.md](.github/skills/honey-bee-storefront-design/SKILL.md) - **Copilot skill for Honey Bee storefront (Stitch design system)**

### Business
- [docs/12-business-model-strategy.md](docs/12-business-model-strategy.md) - White-label model
- [docs/14-visual-overview.md](docs/14-visual-overview.md) - Business diagrams

## Common Pitfalls to Avoid

❌ **Tenant data leakage** - Always verify tenant isolation
❌ **Missing API documentation** - Document controllers with Scribe
❌ **Not updating PROGRESS.md** - Update after every significant milestone
❌ **Missing SEO metadata** - All products/categories need meta tags, schema markup
❌ **Poor URL slugs** - Use lowercase, hyphen-separated, keyword-rich slugs
❌ **No structured data** - Products need Schema.org Product markup for rich snippets
❌ **Missing alt text** - All images need descriptive alt text for accessibility + SEO
❌ **Duplicate meta tags** - Every product needs unique title/description
❌ **Client-side only rendering** - Use SSG for storefronts (SEO critical)
❌ **No sitemap.xml** - Each store needs sitemap for search engine discovery
❌ **Hardcoded store IDs** - Use `tenant()->id`
❌ **Editing old migrations** - Create new ones
❌ **Business logic in controllers** - Use service classes
❌ **Missing TypeScript types** - Type everything
❌ **Committing secrets** - Use `.env`, never commit
❌ **Forgetting to commit progress** - Commit frequently with clear messages

## Performance Targets

- API response: < 200ms (p95)
- Admin panel load: < 2s
- Storefront load: < 1s (static)
- Database queries: < 50ms (indexed)
- Lighthouse score: > 90

## Deployment

See [docs/08-scalability.md](docs/08-scalability.md) for:
- Horizontal scaling strategies
- Caching layers
- Queue workers
- CDN setup

---

**When in doubt**: Check the docs/ folder or ask for clarification. This is a production-grade system serving multiple clients - quality and security are paramount.
