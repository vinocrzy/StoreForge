# Phase 8.2: Storefront API Integration

**Feature ID**: PHASE-8.2  
**Priority**: P0 - Critical (Blocker for Production Launch)  
**Affects**: Storefront (client-honey-bee/)  
**Dependencies**: Phase 8.1 (Public Storefront Backend APIs - COMPLETE)  
**Estimated Effort**: 2-3 days  
**Assigned To**: Storefront Frontend Dev  
**Created**: April 10, 2026  
**Status**: Ready for Implementation

---

## Problem Statement

The Honey Bee storefront (client-honey-bee/) currently uses hardcoded mock data arrays across all pages despite having a fully configured API client and 19 working public backend APIs. This prevents the storefront from displaying real product catalog data, functioning cart, and order processing.

**Impact**: 
- Storefront cannot be deployed to production
- Cannot demonstrate functional e-commerce flow to clients
- Phase 7 (Storefront UI) is 80% complete but functionally blocked

**Business Value**: 
- Unblock production deployment of first client storefront
- Enable end-to-end e-commerce flow testing
- Complete Phase 7 → Phase 8 transition (55% → 70% production readiness)

---

## User Stories

### Story 1: Browse Real Product Catalog
**As a** shopper visiting Honey Bee's storefront  
**I want to** see real products from the database  
**So that** I can browse and purchase actual inventory

**Acceptance Criteria**:
- [ ] Homepage displays real featured products (API: `GET /public/products?is_featured=1`)
- [ ] Homepage displays real product collections/categories (API: `GET /public/categories`)
- [ ] Products page displays paginated product list with filters (API: `GET /public/products` with filters)
- [ ] Products page supports search, category filter, price range filter, and sorting
- [ ] Product detail page loads single product by slug (API: `GET /public/products/{slug}`)
- [ ] All product images, prices, descriptions display correctly
- [ ] Loading states show while fetching data
- [ ] Error states display if API fails (with retry option)

### Story 2: Manage Shopping Cart
**As a** shopper  
**I want to** add products to my cart and modify quantities  
**So that** I can prepare my order before checkout

**Acceptance Criteria**:
- [ ] Guest cart created on first item add (API: `POST /public/cart`)
- [ ] Cart token stored in localStorage for guest persistence
- [ ] Add to cart button calls API and updates cart state (API: `POST /public/cart/items`)
- [ ] Cart page displays real cart items from API (API: `GET /public/cart/{token}`)
- [ ] Quantity updates call API and recalculate totals (API: `PATCH /public/cart/items/{id}`)
- [ ] Remove item calls API and updates state (API: `DELETE /public/cart/items/{id}`)
- [ ] Cart badge in header shows accurate item count
- [ ] Free shipping threshold calculation works with real totals
- [ ] Cart persists across page refreshes (via token)

### Story 3: Complete Checkout (Guest)
**As a** guest shopper  
**I want to** checkout without creating an account  
**So that** I can quickly complete my purchase

**Acceptance Criteria**:
- [ ] Checkout page loads cart items from API
- [ ] Guest checkout form requires: name, email, phone, shipping address
- [ ] Phone number field uses E.164 format (+12025551234)
- [ ] Form validation shows clear error messages
- [ ] Submit checkout calls guest order API (API: `POST /public/checkout/guest`)
- [ ] On success, redirects to order confirmation page
- [ ] Order confirmation displays order details with order number
- [ ] Cart clears after successful order creation
- [ ] Error handling for out-of-stock items during checkout

### Story 4: Customer Account Features
**As a** returning customer  
**I want to** register an account and login  
**So that** I can view my order history and saved addresses

**Acceptance Criteria**:
- [ ] Customer can register with phone (primary) or email (API: `POST /public/customer/register`)
- [ ] Phone number must be in E.164 format during registration
- [ ] Customer can login with phone or email (API: `POST /public/customer/login`)
- [ ] Auth token stored in localStorage on successful login
- [ ] Logged-in customers see "My Account" link in header
- [ ] Account page displays customer profile (API: `GET /public/customer/profile`)
- [ ] Account page shows order history (API: `GET /public/customer/orders`)
- [ ] Customer can logout (API: `POST /public/customer/logout`)
- [ ] Authenticated checkout pre-fills customer details
- [ ] Guest cart merges with customer cart on login

---

## Acceptance Criteria (Technical)

### API Integration
- [ ] All API calls use existing `apiClient` from `src/lib/apiClient.ts`
- [ ] All API endpoints match Phase 8.1 public API routes (`/api/v1/public/*`)
- [ ] API responses match expected TypeScript types
- [ ] X-Store-ID header automatically included in all requests (currently store_id=1)
- [ ] Authorization header included when customer is logged in

### State Management
- [ ] Cart state managed via React Context or useState at app level
- [ ] Guest cart token persists in localStorage (`cart_token`)
- [ ] Customer auth token persists in localStorage (`customer_token`)
- [ ] Cart badge updates across all pages when items change
- [ ] State updates immediately after API success (optimistic UI optional)

### Error Handling
- [ ] Network errors show user-friendly messages
- [ ] Validation errors display inline on forms
- [ ] Out-of-stock errors during checkout show specific product
- [ ] API timeout shows retry option
- [ ] 401 errors clear auth token and redirect to login
- [ ] All errors logged to console for debugging

### Loading States
- [ ] Products page shows skeleton loaders while fetching
- [ ] Cart page shows loading spinner during updates
- [ ] Checkout button disabled during submission
- [ ] Add to cart button shows loading state
- [ ] Navigation remains functional during async operations

### SEO & Performance
- [ ] Product pages remain server-side rendered (SSR)
- [ ] Homepage uses static generation with API calls in `getStaticProps`
- [ ] Dynamic product data fetched client-side or via ISR (revalidate: 3600)
- [ ] Images continue using Next.js Image optimization
- [ ] Meta tags remain accurate for all pages

---

## Out of Scope (MVP)

The following features are explicitly OUT of SCOPE for Phase 8.2:

### Deferred to Phase 8.3+
- ❌ **Product reviews and ratings** — No API exists yet
- ❌ **Wishlist functionality** — Requires new backend feature
- ❌ **Product comparison** — Future enhancement
- ❌ **Advanced filters** (brand, ingredients, skin type) — Basic filters only
- ❌ **Real-time inventory updates** — Polling or WebSockets (future)
- ❌ **Payment gateway integration** — Manual payment only (see docs/17-payment-strategy.md)
- ❌ **Order tracking page** — Admin marks status, no customer portal yet
- ❌ **Customer profile editing** — View only for MVP
- ❌ **Saved addresses** — Future account feature
- ❌ **Multi-currency support** — USD only for MVP

### Hardcoded Content (Intentional for MVP)
- ✅ **Keep:** Homepage hero content, "About Our Process" section, footer content
- ✅ **Keep:** Static marketing copy and brand story
- ✅ **Keep:** FAQ section and shipping policy text
- ✅ **Keep:** Newsletter signup (no backend integration yet)

---

## Implementation Tasks

### Task 1: Create API Service Files (1-2 hours)
**Assigned To**: Storefront Frontend Dev

Create missing service files in `client-honey-bee/src/services/`:

```typescript
// src/services/cart.ts
- createCart(): Promise<{ token: string }> → POST /public/cart
- getCart(token: string): Promise<Cart> → GET /public/cart/{token}
- addItem(token, productId, quantity): Promise<Cart> → POST /public/cart/items
- updateItem(token, itemId, quantity): Promise<Cart> → PATCH /public/cart/items/{id}
- removeItem(token, itemId): Promise<Cart> → DELETE /public/cart/items/{id}
- clearCart(token): Promise<void> → DELETE /public/cart/{token}

// src/services/customer.ts
- register(data): Promise<{ token: string, customer: Customer }> → POST /public/customer/register
- login(login, password): Promise<{ token: string, customer: Customer }> → POST /public/customer/login
- logout(): Promise<void> → POST /public/customer/logout
- getProfile(): Promise<Customer> → GET /public/customer/profile
- getOrders(): Promise<Order[]> → GET /public/customer/orders
- getOrderDetail(id): Promise<Order> → GET /public/customer/orders/{id}

// src/services/checkout.ts
- guestCheckout(data): Promise<{ order: Order }> → POST /public/checkout/guest
- authenticatedCheckout(data): Promise<{ order: Order }> → POST /public/checkout
```

**Validation**:
- [ ] All service files TypeScript type-safe
- [ ] All functions use existing `apiClient`
- [ ] Error handling implemented with `getErrorMessage` helper

### Task 2: Create TypeScript Types (30 mins)
**Assigned To**: Storefront Frontend Dev

Add missing types to `client-honey-bee/src/types/`:

```typescript
// types/cart.ts
export interface Cart {
  id: number;
  token: string;
  items: CartItem[];
  subtotal: number;
  tax: number;
  shipping: number;
  total: number;
  expires_at: string;
}

export interface CartItem {
  id: number;
  product_id: number;
  product_name: string;
  product_slug: string;
  product_image: string;
  variant_id?: number;
  quantity: number;
  unit_price: number;
  total_price: number;
}

// types/customer.ts
export interface Customer {
  id: number;
  name: string;
  email: string;
  phone: string;
  created_at: string;
}

// types/checkout.ts
export interface CheckoutData {
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  shipping_address: Address;
  billing_address?: Address;
  notes?: string;
}

export interface Address {
  line1: string;
  line2?: string;
  city: string;
  state: string;
  postal_code: string;
  country: string;
  phone: string;
}
```

### Task 3: Update Homepage (2-3 hours)
**Assigned To**: Storefront Frontend Dev

**File**: `client-honey-bee/src/app/page.tsx`

**Changes**:
1. Replace `favorites` array with API call:
   ```typescript
   const favorites = await getFeaturedProducts(3);
   ```
2. Replace `collections` array with categories API:
   ```typescript
   const collections = await getCategories(); // Top 3 categories
   ```
3. Add loading state for client-side data fetching
4. Handle API errors gracefully (show fallback or retry)
5. Keep static content (hero, features, story sections) as-is

**Testing**:
- [ ] Homepage loads without errors
- [ ] Featured products display real data
- [ ] Categories display correct images and links
- [ ] Page remains SEO-friendly (SSR/SSG)

### Task 4: Update Products Page (3-4 hours)
**Assigned To**: Storefront Frontend Dev

**Files**: 
- `client-honey-bee/src/app/products/page.tsx`
- `client-honey-bee/src/app/products/ShopClientShell.tsx`

**Changes**:
1. Replace `PRODUCTS` array with `getProducts()` API call
2. Wire filter sidebar to API parameters:
   - Search query → `?search=`
   - Category filter → `?category_id=`
   - Price range → `?min_price=&max_price=`
   - Sort order → `?sort_by=&sort_order=`
3. Implement pagination with API `meta` response
4. Add loading skeleton during fetch
5. Handle empty state (no products found)
6. Handle API errors with retry button

**Testing**:
- [ ] Products load from API on initial page load
- [ ] Filter changes trigger new API call
- [ ] Pagination works correctly
- [ ] Search returns relevant results
- [ ] Category filter shows only products in that category
- [ ] Sort order changes update product list

### Task 5: Update Product Detail Page (2 hours)
**Assigned To**: Storefront Frontend Dev

**File**: `client-honey-bee/src/app/products/[slug]/page.tsx`

**Changes**:
1. Replace mock product data with `getProductBySlug(slug)` API call
2. Implement "Add to Cart" button:
   - On click → Call `addItem()` API
   - Store cart token in localStorage if new guest
   - Update cart badge in header
   - Show success message (toast or inline)
3. Handle out-of-stock products (disable button, show message)
4. Add error handling for invalid slug (404)
5. Keep related products section (if API supports it)

**Testing**:
- [ ] Product detail page loads for valid slug
- [ ] 404 page shown for invalid slug
- [ ] Add to cart creates cart on first click (guest)
- [ ] Add to cart updates existing cart
- [ ] Cart badge updates after adding item
- [ ] Out-of-stock products cannot be added

### Task 6: Update Cart Page (3 hours)
**Assigned To**: Storefront Frontend Dev

**File**: `client-honey-bee/src/app/cart/page.tsx`

**Changes**:
1. Replace `INITIAL_CART` with `getCart(token)` API call
2. Load cart token from localStorage on mount
3. Wire quantity update buttons to `updateItem()` API
4. Wire remove button to `removeItem()` API
5. Recalculate totals after each change (API returns updated totals)
6. Show empty cart state if no items
7. Handle expired cart token (create new cart)
8. Add loading states for all cart operations

**Testing**:
- [ ] Cart loads items from API
- [ ] Quantity increase/decrease updates via API
- [ ] Remove item deletes from API and updates UI
- [ ] Totals recalculate correctly
- [ ] Free shipping threshold displays correctly
- [ ] Empty cart shows fallback message
- [ ] Cart persists across page refreshes

### Task 7: Update Checkout Page (4-5 hours)
**Assigned To**: Storefront Frontend Dev

**File**: `client-honey-bee/src/app/checkout/page.tsx`

**Changes**:
1. Replace `ORDER_ITEMS` with `getCart(token)` API call
2. Build checkout form with fields:
   - Customer name, email, phone (E.164 format)
   - Shipping address (line1, line2, city, state, postal_code, country, phone)
   - Optional billing address
   - Order notes (optional)
3. Add phone number formatting helper (E.164 enforcer)
4. Implement form validation (Formik or React Hook Form)
5. Wire submit to `guestCheckout()` or `authenticatedCheckout()` API
6. On success → Redirect to `/orders/confirmation?order={id}`
7. On error → Show validation errors inline
8. Handle out-of-stock errors during submission
9. Clear cart after successful order

**Testing**:
- [ ] Checkout form pre-fills from cart API
- [ ] Phone number validation enforces E.164 format
- [ ] Form validation prevents submission with invalid data
- [ ] Guest checkout API called when not logged in
- [ ] Authenticated checkout API called when logged in
- [ ] Success redirects to confirmation page
- [ ] Errors display clearly on form
- [ ] Cart clears after successful order

### Task 8: Create Customer Account Features (3-4 hours)
**Assigned To**: Storefront Frontend Dev

**New Files**:
- `client-honey-bee/src/app/account/page.tsx` (profile + order history)
- `client-honey-bee/src/app/account/login/page.tsx`
- `client-honey-bee/src/app/account/register/page.tsx`

**Changes**:
1. Build registration form (name, email, phone, password)
2. Build login form (phone/email, password)
3. Store auth token in localStorage on success
4. Build account dashboard showing customer info
5. Display order history table with order details links
6. Add logout button that clears token
7. Update header to show "My Account" when logged in
8. Protect account routes (redirect to login if not authenticated)

**Testing**:
- [ ] Customer can register new account
- [ ] Customer can login with phone or email
- [ ] Auth token persists across page refreshes
- [ ] Account page shows customer profile from API
- [ ] Order history displays all customer orders
- [ ] Logout clears token and redirects
- [ ] Protected routes redirect unauthenticated users

### Task 9: Global Cart State Management (2 hours)
**Assigned To**: Storefront Frontend Dev

**File**: `client-honey-bee/src/contexts/CartContext.tsx` (new)

**Changes**:
1. Create React Context for cart state
2. Wrap app layout with CartProvider
3. Expose cart functions: `addToCart`, `updateItem`, `removeItem`, `clearCart`
4. Maintain cart item count for header badge
5. Load cart on app mount if token exists
6. Sync cart when customer logs in (merge guest cart)

**Testing**:
- [ ] Cart badge updates across all pages
- [ ] Cart state persists during navigation
- [ ] Cart merges when customer logs in
- [ ] Cart clears after checkout success

### Task 10: Error Handling & Loading States (1-2 hours)
**Assigned To**: Storefront Frontend Dev

**Files**: All pages with API calls

**Changes**:
1. Add loading skeletons for product lists
2. Add loading spinners for cart operations
3. Add error boundaries for critical sections
4. Implement retry logic for failed API calls
5. Add toast notifications for success/error actions
6. Handle 401 errors globally (clear token, redirect)

**Testing**:
- [ ] Loading states show during API calls
- [ ] Error messages display on API failures
- [ ] Retry buttons work for failed requests
- [ ] Success messages show for cart actions
- [ ] Auth errors clear token and redirect

---

## Testing Requirements

### Manual Testing Checklist
- [ ] **Browse products**: Homepage → Products page → Product detail
- [ ] **Add to cart**: Add 3 different products
- [ ] **Update cart**: Change quantities, remove items
- [ ] **Guest checkout**: Complete order without login (use real phone: +15555551234)
- [ ] **Customer registration**: Register new account with phone
- [ ] **Customer login**: Login with phone and password
- [ ] **Authenticated checkout**: Complete order while logged in
- [ ] **View order history**: See past orders in account page
- [ ] **Logout**: Clear session and verify redirect

### Cross-Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile Safari (iOS)
- [ ] Mobile Chrome (Android)

### Responsive Testing
- [ ] Mobile (375px - iPhone SE)
- [ ] Tablet (768px - iPad)
- [ ] Desktop (1920px - Full HD)

### Performance Testing
- [ ] Lighthouse score > 90
- [ ] Time to Interactive < 3s
- [ ] API calls < 500ms (backend local)
- [ ] Images optimized via Next.js Image

---

## Notes for Tech Lead

### Architecture Decisions
- **No state management library**: Use React Context for cart state (sufficient for MVP). Redux/Zustand can be added later if needed.
- **Server vs. Client components**: Homepage and product pages should use SSR/SSG. Cart and checkout are client-side only.
- **Phone number format**: Enforce E.164 format (`+12025551234`) on frontend with validation helper. Backend already validates this.
- **Cart persistence**: Guest carts use token-based API, not localStorage for cart items. Only store token.
- **Auth persistence**: Customer JWT token stored in localStorage. Use httpOnly cookies in production (future enhancement).

### API Base URL
Current: `http://localhost:8000/api/v1/public`  
Production: `https://api.honeybee.com/api/v1/public`

Ensure `.env.local` is configured:
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1/public
NEXT_PUBLIC_STORE_ID=1
```

### Known Backend API Endpoints (Phase 8.1)
✅ **Products**: GET /public/products, GET /public/products/{slug}  
✅ **Categories**: GET /public/categories, GET /public/categories/{slug}  
✅ **Cart**: POST /public/cart, GET /public/cart/{token}, POST/PATCH/DELETE /public/cart/items  
✅ **Customer Auth**: POST /public/customer/register, POST /public/customer/login, POST /public/customer/logout  
✅ **Customer Account**: GET /public/customer/profile, GET /public/customer/orders, GET /public/customer/orders/{id}  
✅ **Checkout**: POST /public/checkout/guest, POST /public/checkout

### Risk Mitigation
- **Risk**: API changes during development  
  **Mitigation**: Verify all endpoints with `php artisan route:list --path=public` before starting
  
- **Risk**: Phone number validation inconsistencies  
  **Mitigation**: Use shared E.164 validation helper on frontend matching backend rules

- **Risk**: Cart token expiry (30 days)  
  **Mitigation**: Handle 404 errors on `getCart()` by creating new cart automatically

---

## Definition of Done

Phase 8.2 is complete when:
- ✅ All 10 implementation tasks checked off
- ✅ All acceptance criteria met (36 total criteria)
- ✅ Manual testing checklist 100% passed
- ✅ No TypeScript build errors (`npm run build` succeeds)
- ✅ No console errors on any page
- ✅ PROGRESS.md updated with Phase 8.2 status
- ✅ Honey Bee storefront deployed to staging environment
- ✅ Tech Lead sign-off on end-to-end flow test

---

## Success Metrics

### Before Phase 8.2 (Current State)
- Storefront uses 100% mock data (5 hardcoded arrays)
- 0 functional API integrations
- Cannot process real orders
- Production deployment blocked

### After Phase 8.2 (Target State)
- Storefront uses 100% real data from backend
- 19 API endpoints integrated
- End-to-end order flow functional
- Production deployment unblocked
- **Production Readiness: 55% → 70%**

---

## Next Steps (Post-Phase 8.2)

After completing Phase 8.2:
1. **Phase 8.3**: Production Server Setup + CI/CD Pipeline (~2 weeks)
2. **Phase 9**: Testing & QA (E2E tests, load testing) (~2 weeks)
3. **Phase 10**: Launch Preparation (docs, client onboarding) (~1 week)

**Estimated Production Launch**: Late May 2026 (~5 weeks from Phase 8.2 completion)

---

**Document Owner**: Product Manager  
**Last Updated**: April 10, 2026  
**Review Date**: Weekly during implementation
