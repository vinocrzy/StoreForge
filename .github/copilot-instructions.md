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
| Admin | React + TypeScript | 18+ | Store management dashboard |
| Storefront | Next.js | 14+ | Client-facing stores (SSG) |
| Database | MySQL/PostgreSQL | 8.0+/14+ | Multi-tenant data |
| Cache | Redis | 7+ | Sessions, cache, queues |
| Auth | Laravel Sanctum | - | Token-based API auth |
| UI | Ant Design / Tailwind | - | Component libraries |
| State | Redux Toolkit | - | Admin panel state |

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

### Frontend (React)

**TypeScript Always**: No `.jsx` files, use `.tsx`

**Component Structure**:
```typescript
// ✅ Good - typed props, hooks, clear return
interface ProductListProps {
  storeId: number;
  status?: 'active' | 'draft';
}

export const ProductList: React.FC<ProductListProps> = ({ storeId, status }) => {
  const { data, isLoading } = useGetProductsQuery({ storeId, status });
  
  if (isLoading) return <Spin />;
  return <Table dataSource={data} />;
};

// ❌ Bad - no types, unclear structure
export default function ProductList(props) {
  const data = useGetProductsQuery(props.storeId);
  return <Table dataSource={data} />;
}
```

**State Management**: Use RTK Query for API calls
```typescript
// ✅ Good - RTK Query
const { data: products } = useGetProductsQuery({ storeId, page: 1 });

// ❌ Bad - manual fetch
const [products, setProducts] = useState([]);
useEffect(() => {
  fetch('/api/products').then(r => r.json()).then(setProducts);
}, []);
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

See [docs/16-api-documentation-system.md](docs/16-api-documentation-system.md) for complete guide.

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

# Frontend
npm test
npm run lint
npm run type-check
```

### Creating Client Storefront
```bash
scripts/create-client-store.bat "Client Name" store_id
```

## Key Documentation

### Getting Started
- [docs/11-getting-started.md](docs/11-getting-started.md) - Setup guide
- [docs/13-implementation-priority.md](docs/13-implementation-priority.md) - What to build first

### Architecture
- [docs/01-system-architecture.md](docs/01-system-architecture.md) - High-level design
- [docs/07-multi-tenancy.md](docs/07-multi-tenancy.md) - Tenant isolation strategy
- [docs/15-repository-structure.md](docs/15-repository-structure.md) - Multi-repo workflow

### Implementation
- [docs/02-backend-architecture.md](docs/02-backend-architecture.md) - Laravel patterns
- [docs/03-database-schema.md](docs/03-database-schema.md) - Complete schema
- [docs/04-api-design.md](docs/04-api-design.md) - API specifications
- [docs/16-api-documentation-system.md](docs/16-api-documentation-system.md) - API docs setup

### Business
- [docs/12-business-model-strategy.md](docs/12-business-model-strategy.md) - White-label model
- [docs/14-visual-overview.md](docs/14-visual-overview.md) - Business diagrams

## Common Pitfalls to Avoid

❌ **Tenant data leakage** - Always verify tenant isolation
❌ **Missing API documentation** - Document controllers with Scribe
❌ **Hardcoded store IDs** - Use `tenant()->id`
❌ **Client-side only rendering** - Use SSG for storefronts (SEO)
❌ **Editing old migrations** - Create new ones
❌ **Business logic in controllers** - Use service classes
❌ **Missing TypeScript types** - Type everything
❌ **Committing secrets** - Use `.env`, never commit

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
