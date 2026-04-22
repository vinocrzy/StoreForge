# Phase 9: Commercial Viability Work Plan

**Document Type**: Implementation Work Plan  
**Created**: April 22, 2026  
**Author**: Product Manager  
**Source**: [Competitive Gap Analysis](../analysis/2026-04-22-competitive-gap-analysis.md)  
**Status**: 📋 APPROVED — Ready for Implementation  
**Goal**: Convert StoreForge from "demo-ready MVP" to "commercially viable product"

---

## Executive Summary

StoreForge is live in production (Honey Bee at honeybee.net.in) with a solid multi-tenant foundation. However, 5 critical feature gaps prevent acquiring paying clients at scale. This work plan defines **10 features across 3 sub-phases** (9A, 9B, 9C) that close the gap between "functional demo" and "product clients will pay $49–$499/month for."

**Total estimated engineering effort**: 16–22 weeks  
**Revenue unlock**: Payment gateway alone enables all transaction-based revenue  
**Client retention unlock**: Emails + reviews + wishlists + promotions = professional-grade experience

---

## Work Plan Overview

| Sub-Phase | Name | Duration | Features | Priority |
|-----------|------|----------|----------|----------|
| **9A** | Foundation — Commerce Essentials | Weeks 1–4 | 5 features | P0 (Must Have) |
| **9B** | Competitive Parity | Weeks 5–10 | 5 features | P1 (High) |
| **9C** | Differentiation | Weeks 11–18 | 4 features | P2 (Medium) |

### Dependency Map

```
Week 1 ─── Transactional Emails (9A-1) ────────────────┐
Week 1-2 ─ Wishlists (9A-2) ───────────────────────────┤
Week 2-3 ─ Product Reviews (9A-3) ─────────────────────┤
Week 2-4 ─ Payment Gateway (9A-4) ──────────┐          │
Week 3-4 ─ Coupon Engine (9A-5) ────────────┤          │
                                             │          │
Week 5 ─── Abandoned Cart Recovery (9B-1) ──┤◄─────────┘ (requires 9A-1 emails)
Week 5-6 ─ Tax Calculation (9B-2) ──────────┤
Week 6-8 ─ Shipping Rates (9B-3) ──────────┤
Week 7-9 ─ Analytics Dashboard (9B-4) ─────┤
Week 9-10 ─ Returns & Refunds (9B-5) ──────┘◄────────── (requires 9A-4 payments)
                                             
Week 11-12 ─ AI Recommendations (9C-1) ────┐
Week 13-14 ─ Email Marketing (9C-2) ───────┤
Week 14-16 ─ Multi-Currency (9C-3) ────────┤
Week 16-18 ─ Loyalty Program (9C-4) ───────┘
```

---

## Sub-Phase 9A: Foundation — Commerce Essentials (P0)

**Duration**: Weeks 1–4  
**Goal**: Every feature here is a hard prerequisite for charging clients real money.  
**Exit Criteria**: A Honey Bee customer can browse → add to cart → apply coupon → pay by card → receive order confirmation email → leave a review → save items to wishlist.

---

### 9A-1: Transactional Email System

**Priority**: P0 — Must Have  
**Affects**: Backend  
**Estimated Effort**: 1–2 weeks  
**Dependencies**: None (start immediately)  
**Assigned To**: Backend Developer

#### Problem Statement
The platform sends zero automated emails. No order confirmation, no shipping update, no password reset. Customers have no visibility into their orders and vendors lose the primary trust-building channel.

#### User Stories
1. As a **customer**, I want to receive an email when I place an order, so that I have confirmation and can reference my order details.
2. As a **customer**, I want to receive an email when my order ships, so that I know it's on the way and can track it.
3. As a **store admin**, I want the system to send branded emails automatically, so that I don't have to contact every customer manually.
4. As a **customer**, I want to receive a password reset email, so that I can recover my account.

#### Acceptance Criteria

**Core Emails (MVP)**:
- [ ] Given a customer places an order, when the order is created, then an order confirmation email is sent within 60 seconds containing: order number, item list with quantities and prices, total amount, shipping address
- [ ] Given an admin changes order status to "shipped", when the status is saved, then a shipping notification email is sent to the customer containing: order number, tracking info (if available), estimated delivery text
- [ ] Given an admin changes order status to "delivered", when the status is saved, then a delivery confirmation email is sent to the customer
- [ ] Given a customer requests a password reset, when the request is submitted, then a password reset email with a secure time-limited link is sent within 30 seconds
- [ ] Given a new customer registers, when the account is created, then a welcome email is sent with the store name and a link to start shopping

**Tenant Branding**:
- [ ] Given a store has a logo and brand colors configured in store_settings, when any email is sent, then the email uses the store's logo in the header and store name in the subject line
- [ ] Given Store A sends an email, when a customer receives it, then the email shows Store A's branding (not Store B's or the platform's)

**Configuration**:
- [ ] Given the platform uses an SMTP/Mailgun driver, when emails are queued, then they are sent via Laravel's queue system (not synchronously blocking the request)
- [ ] Given an email fails to send (SMTP timeout), when the failure is logged, then the system retries up to 3 times with exponential backoff

**Edge Cases**:
- [ ] Customer with no email (guest checkout with phone only) → skip email, no error
- [ ] Invalid email format → log warning, don't crash the order flow
- [ ] High volume (100 orders/minute) → queue processes asynchronously, no request blocking

#### Out of Scope (MVP)
- Marketing/promotional emails (see 9C-2)
- SMS notifications (future enhancement)
- Customer-facing email template editor in admin
- Email open/click tracking analytics

#### Technical Notes for Tech Lead
- Use Laravel Notifications + Mailables (already supported by framework)
- Queue all emails via `ShouldQueue` — never send synchronously
- Use configurable mail driver per store (store_settings key: `mail_driver`, `mail_from_address`, `mail_from_name`)
- Default to platform-level SMTP config if store doesn't configure its own
- Email templates: Blade templates in `resources/views/emails/`
- Consider using `markdown` mailables for consistent styling

#### Email Template List (MVP)

| Email | Trigger | To | Subject Format |
|-------|---------|-----|----------------|
| Order Confirmation | Order created | Customer | `[StoreName] Order #ORD-XXX Confirmed` |
| Payment Received | payment_status → paid | Customer | `[StoreName] Payment Received for Order #ORD-XXX` |
| Order Shipped | status → shipped | Customer | `[StoreName] Your Order #ORD-XXX Has Shipped` |
| Order Delivered | status → delivered | Customer | `[StoreName] Your Order #ORD-XXX Has Been Delivered` |
| Password Reset | Customer requests reset | Customer | `[StoreName] Reset Your Password` |
| Welcome Email | Customer registers | Customer | `Welcome to [StoreName]!` |

---

### 9A-2: Wishlist Functionality

**Priority**: P0 — Must Have  
**Affects**: Backend + Storefront  
**Estimated Effort**: 1 week  
**Dependencies**: None (can start Week 1 in parallel with 9A-1)  
**Assigned To**: Backend Developer (API) + Storefront Frontend Dev (UI)

#### Problem Statement
Customers cannot save products for later. Wishlists drive return visits (+25% return visitor rate), provide free demand intelligence data, and are an expected feature on any e-commerce site.

#### User Stories
1. As a **customer**, I want to save a product to my wishlist, so that I can easily find and buy it later.
2. As a **customer**, I want to view all my saved items in one page, so that I can decide what to buy.
3. As a **customer**, I want to remove items from my wishlist, so that I can keep it organized.
4. As a **store admin**, I want to see which products are most wishlisted, so that I can understand demand.

#### Acceptance Criteria

**Customer-Facing**:
- [ ] Given I am a logged-in customer on a product card, when I click the heart icon, then the product is added to my wishlist and the heart icon becomes filled/solid
- [ ] Given I have a product in my wishlist and I click the filled heart icon, when the request completes, then the product is removed from my wishlist and the heart icon becomes outlined
- [ ] Given I navigate to `/account/wishlist`, when the page loads, then I see all my wishlisted products with: product image, name, price, "Add to Cart" button, "Remove" button
- [ ] Given I click "Add to Cart" on a wishlisted item, when the item is added to cart, then the product remains in my wishlist (user must explicitly remove)
- [ ] Given a wishlisted product is out of stock, when I view my wishlist, then I see "Out of Stock" label instead of "Add to Cart" button

**API**:
- [ ] `POST /api/v1/storefront/wishlist` with `{ product_id }` → 201 (added)
- [ ] `DELETE /api/v1/storefront/wishlist/{product_id}` → 204 (removed)
- [ ] `GET /api/v1/storefront/wishlist` → 200 with paginated product list
- [ ] `GET /api/v1/storefront/wishlist/check/{product_id}` → 200 `{ wishlisted: true/false }`
- [ ] Given a guest (unauthenticated) user tries to add to wishlist, when the request is made, then return 401 with message "Please log in to save items"

**Admin (Reporting)**:
- [ ] `GET /api/v1/admin/reports/most-wishlisted` → top 10 products by wishlist count for the current store

**Tenant Isolation**:
- [ ] Given Store A has 5 wishlisted products and Store B has 3, when Store A admin views most-wishlisted report, then only Store A's data is shown
- [ ] Given Customer X belongs to Store A, when they view their wishlist, then only products from Store A appear

**Database Schema**:
```sql
CREATE TABLE wishlists (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uk_customer_product (store_id, customer_id, product_id),
    INDEX idx_store_id (store_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_product_id (product_id)
);
```

#### Out of Scope (MVP)
- "Price drop" notification emails for wishlisted items (requires 9A-1 + 9C-2)
- "Back in stock" alerts (future enhancement)
- Shared/public wishlists
- Guest wishlist (localStorage-based)

#### Technical Notes for Tech Lead
- Simple pivot-style table, no complex logic
- Batch-check endpoint for product listing pages (check multiple product_ids at once): `POST /api/v1/storefront/wishlist/check` with `{ product_ids: [1,2,3] }` → `{ 1: true, 2: false, 3: true }`
- Storefront: heart icon component reusable across ProductCard and ProductDetail

---

### 9A-3: Product Reviews & Ratings

**Priority**: P0 — Must Have  
**Affects**: Backend + Admin Panel + Storefront  
**Estimated Effort**: 2 weeks  
**Dependencies**: None (can start Week 2)  
**Assigned To**: Backend Developer (API + Schema) + Admin Frontend Dev (moderation UI) + Storefront Frontend Dev (display + submit)

#### Problem Statement
Products with reviews convert 270% more than those without (Spiegel Research Center). StoreForge has zero review capability. Additionally, Google displays star ratings in search results via AggregateRating Schema.org — a significant SEO advantage we're missing.

#### User Stories
1. As a **customer who purchased a product**, I want to leave a review with a star rating, so that other shoppers benefit from my experience.
2. As a **shopper**, I want to see reviews and average ratings on product pages, so that I can make an informed purchase decision.
3. As a **shopper**, I want to see star ratings on product listing cards, so that I can quickly identify well-reviewed products.
4. As a **store admin**, I want to approve or reject customer reviews, so that I can maintain quality and prevent spam.
5. As a **store admin**, I want to respond to customer reviews, so that I can address feedback publicly.

#### Acceptance Criteria

**Submitting Reviews**:
- [ ] Given I am a logged-in customer who has a "delivered" order containing Product X, when I visit Product X's page and click "Write a Review", then I see a review form with: star rating (1-5, required), review title (optional, max 100 chars), review body (required, 20-2000 chars)
- [ ] Given I submit a valid review, when the request completes, then I see a success message "Your review has been submitted for approval" and the review does NOT appear publicly until admin approves it
- [ ] Given I have already reviewed Product X, when I try to submit another review, then I see an error "You have already reviewed this product"
- [ ] Given I have NOT purchased Product X (no delivered order), when I try to submit a review, then I see a message "Only verified buyers can review this product"

**Displaying Reviews**:
- [ ] Given Product X has 5 approved reviews, when I view the product page, then I see: average rating (e.g., 4.2/5), total review count, individual reviews sorted by newest first with: reviewer name (first name + last initial), star rating, title, body, date, "Verified Purchase" badge
- [ ] Given Product X has 0 approved reviews, when I view the product page, then I see "No reviews yet. Be the first to review!" with a CTA to write a review
- [ ] Given a product listing page (cards), when I view a product card, then I see the average star rating and review count (e.g., "★★★★☆ (12)")

**Admin Moderation**:
- [ ] Given 3 new reviews are pending, when admin opens the Reviews page in admin panel, then they see a list of pending reviews with: product name, customer name, rating, title, body, date, "Approve" / "Reject" buttons
- [ ] Given admin clicks "Approve" on a review, when the action completes, then the review status changes to "approved" and it becomes visible on the storefront
- [ ] Given admin clicks "Reject" on a review, when the action completes, then the review status changes to "rejected" and a rejection reason field is available
- [ ] Given admin wants to respond to an approved review, when they type a response and submit, then the admin response appears below the customer review on the storefront

**SEO — Schema.org**:
- [ ] Given Product X has 3+ approved reviews, when the product page is rendered (Next.js), then the page includes `AggregateRating` JSON-LD markup with `ratingValue`, `reviewCount`, `bestRating: 5`, `worstRating: 1`
- [ ] Given Product X has approved reviews, when the product page is rendered, then each review is included in the `review` array of the Product Schema.org markup

**API Endpoints**:
- [ ] `GET /api/v1/storefront/products/{slug}/reviews` → paginated approved reviews
- [ ] `POST /api/v1/storefront/products/{slug}/reviews` → submit review (auth required)
- [ ] `GET /api/v1/admin/reviews` → list all reviews (filterable by status: pending/approved/rejected)
- [ ] `PATCH /api/v1/admin/reviews/{id}` → update status (approve/reject) + add admin response
- [ ] `DELETE /api/v1/admin/reviews/{id}` → soft delete review

**Tenant Isolation**:
- [ ] Reviews are scoped by store_id — Store A's reviews never appear on Store B
- [ ] Admin panel shows only reviews for the current store

**Database Schema**:
```sql
CREATE TABLE product_reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,           -- Link to the order (verified purchase)
    rating TINYINT UNSIGNED NOT NULL,        -- 1-5
    title VARCHAR(100) NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    admin_response TEXT NULL,
    admin_responded_at TIMESTAMP NULL,
    rejection_reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    UNIQUE KEY uk_customer_product (store_id, customer_id, product_id),
    INDEX idx_store_id (store_id),
    INDEX idx_product_id (product_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at)
);
```

**Product Table Addition**:
```sql
ALTER TABLE products ADD COLUMN (
    avg_rating DECIMAL(2,1) DEFAULT NULL,  -- Cached average rating (updated on review approve/reject)
    review_count INT DEFAULT 0             -- Cached count of approved reviews
);
```

#### Out of Scope (MVP)
- Photo/video reviews
- Review helpfulness voting ("Was this helpful? Yes/No")
- Review sorting by rating, helpfulness
- Automated review request emails (requires 9A-1)
- Incentivized reviews (leave review → get coupon)

#### Technical Notes for Tech Lead
- Cache `avg_rating` and `review_count` on the products table — update via observer/event when review status changes
- Use `is_verified_purchase` flag: set to true if customer has a "delivered" order containing the product
- Admin response is a simple text field, not a threaded conversation
- Schema.org: update the existing Product schema_markup generation to include AggregateRating when review_count > 0

---

### 9A-4: Payment Gateway Integration (Stripe + Razorpay)

**Priority**: P0 — Must Have  
**Affects**: Backend + Storefront  
**Estimated Effort**: 3–4 weeks  
**Dependencies**: None (can start Week 2)  
**Assigned To**: Backend Developer + Storefront Frontend Dev

#### Problem Statement
The platform has manual payments only — customers must pay outside the system, and vendors manually mark orders as paid. No professional e-commerce site operates this way. This is the #1 barrier to conversion and the #1 blocker for acquiring paying clients.

#### User Stories
1. As a **customer**, I want to pay for my order with a credit/debit card at checkout, so that my order is confirmed instantly.
2. As a **customer (India)**, I want to pay via UPI or Razorpay, so that I can use my preferred local payment method.
3. As a **store admin**, I want to choose which payment gateway to use (Stripe or Razorpay), so that I can use the best option for my market.
4. As a **store admin**, I want to process refunds from the admin panel, so that I can handle returns without logging into the payment gateway dashboard.
5. As a **platform operator**, I want payment gateways configured per store, so that each tenant uses their own gateway account.

#### Acceptance Criteria

**Checkout Flow (Customer)**:
- [ ] Given I am on the checkout page with items in my cart, when I enter my card details and click "Pay", then the payment is processed and I see an order confirmation page within 5 seconds
- [ ] Given my card is declined, when the payment fails, then I see a clear error message ("Your card was declined. Please try another payment method.") and my cart is preserved
- [ ] Given I am a Honey Bee customer (India), when I reach the payment step, then I see Razorpay payment options (card, UPI, netbanking)
- [ ] Given payment is processing asynchronously (e.g., UPI), when I wait, then I see a "Payment processing..." state, and the page updates when the webhook confirms payment

**Payment Processing (Backend)**:
- [ ] Given a customer initiates payment, when the payment intent/order is created, then NO raw card data touches our server (use Stripe Elements / Razorpay Checkout.js)
- [ ] Given Stripe sends a `payment_intent.succeeded` webhook, when it's received, then the order's `payment_status` is updated to `paid`, a payment record is created in the `payments` table, and an order confirmation email is triggered (if 9A-1 is complete)
- [ ] Given a webhook arrives for a non-existent order, when processed, then the system logs a warning and returns 200 (acknowledge but ignore)
- [ ] Given a duplicate webhook arrives, when processed, then the system is idempotent — no double-charging or duplicate records

**Gateway Configuration (Per-Store)**:
- [ ] Given a store admin configures Stripe, when they save API keys in store settings, then the store's checkout uses Stripe
- [ ] Given a store admin configures Razorpay, when they save API keys, then the store's checkout uses Razorpay
- [ ] Gateway API keys are stored encrypted in `store_settings` — never in plaintext
- [ ] Given no gateway is configured, when a customer reaches checkout, then the manual payment option is shown (backward compatible)

**Refunds (Admin)**:
- [ ] Given an order is paid via Stripe, when admin clicks "Refund" and enters an amount, then a refund is processed through Stripe's API and the order's `payment_status` is updated to `refunded` or `partially_refunded`
- [ ] Given a full refund is processed, when complete, then the refunded amount matches the original payment amount
- [ ] Given a partial refund is processed, when complete, then the refund amount is recorded and the remaining paid amount is visible

**API Endpoints**:
- [ ] `POST /api/v1/storefront/checkout/payment-intent` → create Stripe PaymentIntent or Razorpay Order
- [ ] `POST /api/v1/webhooks/stripe` → handle Stripe webhooks (no auth, signature verification)
- [ ] `POST /api/v1/webhooks/razorpay` → handle Razorpay webhooks (no auth, signature verification)
- [ ] `POST /api/v1/admin/orders/{id}/refund` → process refund through gateway
- [ ] `GET /api/v1/admin/payments` → list payment transactions for the store

**Security**:
- [ ] Stripe webhook signature verified using `Stripe-Signature` header
- [ ] Razorpay webhook signature verified using `X-Razorpay-Signature` header
- [ ] API keys encrypted at rest in database (use Laravel's `Crypt` facade)
- [ ] PCI compliance maintained: no card data stored, processed, or transmitted by our server (Stripe Elements / Razorpay Checkout.js handle card collection)

**Tenant Isolation**:
- [ ] Each store has its own Stripe/Razorpay account credentials
- [ ] Payment records are scoped by store_id
- [ ] Webhook handlers validate that the payment belongs to the correct store

#### Out of Scope (MVP)
- Apple Pay / Google Pay (Stripe supports these but requires additional setup)
- PayPal integration
- Subscription/recurring billing
- Split payments (pay with card + store credit)
- Payment gateway admin UI for managing keys (use store_settings for now, build dedicated UI later)

#### Technical Notes for Tech Lead
- **Architecture**: Create a `PaymentGatewayInterface` with `StripeGateway` and `RazorpayGateway` implementations. Use `PaymentGatewayFactory` to resolve the correct gateway per store.
- **No Laravel Cashier**: Cashier is designed for subscription billing. Use Stripe PHP SDK directly for one-time payments.
- **Webhook Security**: Use middleware to verify signatures. Return 200 immediately, process asynchronously via queue.
- **Existing schema**: The `payments` table already has `gateway`, `transaction_id`, `metadata` fields — use them.
- **Migration path**: Keep `gateway = 'manual'` working. New orders can use `gateway = 'stripe'` or `gateway = 'razorpay'`.

---

### 9A-5: Coupon & Promotion Engine

**Priority**: P0 — Must Have  
**Affects**: Backend + Admin Panel + Storefront  
**Estimated Effort**: 3–4 weeks  
**Dependencies**: None (can start Week 3)  
**Assigned To**: Backend Developer + Admin Frontend Dev + Storefront Frontend Dev

#### Problem Statement
The database schema for promotions, coupons, and offers exists but nothing is implemented — no models, services, controllers, or UI. Store owners cannot run sales, offer discount codes, or incentivize purchases. 60% of online shoppers search for coupons before buying (RetailMeNot).

#### User Stories
1. As a **store admin**, I want to create a percentage-off coupon code (e.g., "WELCOME10" for 10% off), so that I can offer discounts to new customers.
2. As a **store admin**, I want to create a fixed-amount coupon (e.g., "$5 off orders over $30"), so that I can run promotions with minimum purchase requirements.
3. As a **customer**, I want to enter a coupon code at checkout, so that I receive a discount on my order.
4. As a **store admin**, I want to set usage limits on coupons (total uses, per-customer), so that I can control promotion costs.
5. As a **store admin**, I want to see coupon usage reports, so that I can measure promotion effectiveness.

#### Acceptance Criteria

**Coupon CRUD (Admin)**:
- [ ] Given I am on the admin Coupons page, when I click "Create Coupon", then I see a form with: code (auto-generate or manual), type (percentage/fixed), value, minimum purchase amount, maximum discount amount (for %), usage limit (total), usage limit per customer, start date, end date, status (active/inactive), applies to (all products / specific categories)
- [ ] Given I create a coupon "SUMMER20" for 20% off with $50 minimum and 100 total uses, when I save, then the coupon appears in the coupon list with correct details
- [ ] Given I want to deactivate a coupon early, when I toggle the status to "inactive", then the coupon can no longer be applied at checkout

**Applying Coupons (Customer Checkout)**:
- [ ] Given I am at checkout with a $60 cart and coupon "SUMMER20" (20% off, $50 min), when I enter the code and click "Apply", then I see a discount of $12.00 and the new total is $48.00 + shipping + tax
- [ ] Given I try coupon "SUMMER20" with a $30 cart (below $50 minimum), when I apply it, then I see an error: "Minimum purchase of $50.00 required for this coupon"
- [ ] Given coupon "WELCOME10" has been used 100/100 times, when I try to apply it, then I see: "This coupon has reached its usage limit"
- [ ] Given coupon "FLASH50" expired yesterday, when I try to apply it, then I see: "This coupon has expired"
- [ ] Given I already used "ONETIME" coupon on a previous order (per-customer limit: 1), when I try to apply it again, then I see: "You have already used this coupon"
- [ ] Given I apply a valid coupon, when I proceed to place the order, then the order record stores: `coupon_code`, `discount_amount`
- [ ] Given I applied a coupon and then remove it (click "Remove"), when the coupon is removed, then the cart total reverts to the original amount

**Coupon Validation Rules**:
- [ ] Coupon code is case-insensitive: "summer20" and "SUMMER20" both work
- [ ] Only one coupon per order (MVP — no stacking)
- [ ] Percentage coupons: cap discount at `maximum_discount_amount` if set
- [ ] Fixed coupons: discount cannot exceed cart subtotal (no negative totals)

**API Endpoints**:
- [ ] `POST /api/v1/storefront/coupons/validate` → validate coupon code against cart `{ code, cart_subtotal }` → returns discount amount or error
- [ ] `POST /api/v1/storefront/coupons/apply` → apply coupon to cart/order `{ code, cart_id }` → updates cart with discount
- [ ] `DELETE /api/v1/storefront/coupons/remove` → remove applied coupon from cart
- [ ] `GET /api/v1/admin/coupons` → list coupons (paginated, filterable by status)
- [ ] `POST /api/v1/admin/coupons` → create coupon
- [ ] `GET /api/v1/admin/coupons/{id}` → get coupon details with usage stats
- [ ] `PUT /api/v1/admin/coupons/{id}` → update coupon
- [ ] `DELETE /api/v1/admin/coupons/{id}` → soft delete coupon

**Tenant Isolation**:
- [ ] Coupons are scoped by store_id — Store A's "WELCOME10" is independent of Store B's "WELCOME10"
- [ ] Coupon usage is tracked per store+customer combination

**Database**: Use existing schema (promotions, coupons, coupon_usages tables from [docs/03-database-schema.md](../03-database-schema.md)). Create Laravel models, services, and controllers.

#### Out of Scope (MVP)
- Automatic promotions (applied without a code — e.g., "10% off all soap bars")
- Buy-X-Get-Y offers
- Free shipping coupons
- Coupon stacking
- Bulk coupon code generation (e.g., 1000 unique codes for an influencer campaign)
- Category/product-specific coupons (MVP: all products only)

#### Technical Notes for Tech Lead
- **MVP scope**: Focus on Coupon model only (skip Promotion and Offer models for now). Coupons with code, type, value, limits, dates.
- **Validation service**: Create `CouponService::validate($code, $cartSubtotal, $customerId)` that returns either the discount amount or a specific error.
- **Existing orders table**: Already has `coupon_code` and `discount_amount` fields — use them.
- **Track usage**: Insert into `coupon_usages` when order is placed, increment `coupons.used_count`.

---

## Sub-Phase 9B: Competitive Parity (P1)

**Duration**: Weeks 5–10  
**Goal**: Match WooCommerce-level feature parity. These features close operational gaps and unlock the $199+/month pricing tiers.  
**Exit Criteria**: Stores have abandoned cart recovery, basic tax logic, real shipping rates, an analytics dashboard, and a proper return/refund workflow.

---

### 9B-1: Abandoned Cart Recovery

**Priority**: P1 — High  
**Affects**: Backend  
**Estimated Effort**: 1–2 weeks  
**Dependencies**: 9A-1 (Transactional Email System)  
**Assigned To**: Backend Developer

#### Problem Statement
The average cart abandonment rate is 70.19% (Baymard Institute, 49 studies). Abandoned cart recovery emails recover 5–15% of lost revenue. With even modest GMV, this feature pays for itself immediately.

#### User Stories
1. As a **store admin**, I want abandoned carts to be detected automatically, so that I can recover lost sales.
2. As a **customer who left items in my cart**, I want to receive a reminder email, so that I can return and complete my purchase.
3. As a **store admin**, I want to see abandoned cart statistics, so that I can understand where customers drop off.

#### Acceptance Criteria
- [ ] Given a logged-in customer adds items to cart but does not place an order within 1 hour, when the scheduled job runs, then the cart is flagged as "abandoned"
- [ ] Given a cart is flagged as abandoned, when 1 hour has passed since abandonment, then a recovery email is sent with: cart items list, total amount, "Complete Your Order" CTA link that restores the cart
- [ ] Given a recovery email was sent and the customer has not returned within 24 hours, when the second scheduled job runs, then a second recovery email is sent (max 2 emails per abandoned cart)
- [ ] Given a customer completes their order, when the order is placed, then any pending recovery emails for that cart are cancelled
- [ ] Given admin opens the "Abandoned Carts" section, when the page loads, then they see: total abandoned carts (30 days), recovery rate (%), revenue recovered, list of abandoned carts with customer name, cart value, time abandoned
- [ ] Given a store admin wants to include a recovery incentive, when they configure "abandoned cart coupon" in store settings, then recovery emails include a coupon code (e.g., "COMEBACK10")

**Edge Cases**:
- [ ] Guest carts (no customer email) → cannot send recovery email, skip
- [ ] Customer explicitly empties cart → do not mark as abandoned
- [ ] Customer unsubscribes from marketing → still send transactional recovery emails (these are transactional, not marketing)

#### Out of Scope (MVP)
- SMS recovery notifications
- Push notifications
- A/B testing email subject lines
- Third-party integrations (Klaviyo, Drip)

#### Technical Notes for Tech Lead
- Laravel Scheduler: Run `cart:detect-abandoned` every 15 minutes
- Add `abandoned_at` timestamp to carts table
- Add `recovery_email_sent_at`, `recovery_email_count` fields to carts table
- Use existing email system (9A-1) for sending recovery emails
- Track conversion: if customer completes order within 48 hours of recovery email → count as recovered

---

### 9B-2: Tax Calculation Engine (Phase 1)

**Priority**: P1 — High  
**Affects**: Backend + Storefront  
**Estimated Effort**: 1–2 weeks  
**Dependencies**: None  
**Assigned To**: Backend Developer

#### Problem Statement
The `orders` table has a `tax_amount` field that's always 0. For the Indian market (Honey Bee), GST is legally required. For US clients, sales tax varies by state. Without tax calculation, stores are either non-compliant or must calculate taxes manually.

#### User Stories
1. As a **store admin**, I want to configure tax rates for my store, so that taxes are automatically calculated on orders.
2. As a **customer**, I want to see the tax amount at checkout, so that I know the total cost before paying.
3. As a **store admin (India)**, I want GST calculated automatically (CGST + SGST or IGST), so that my invoices are tax-compliant.

#### Acceptance Criteria
- [ ] Given a store has configured a flat tax rate of 18% (GST), when a customer's cart subtotal is $100, then the checkout displays "Tax: $18.00" and total is $118.00
- [ ] Given a store has configured category-based tax rates (e.g., "Soaps" = 12%, "Essential Oils" = 18%), when a cart has items from both categories, then each item has the correct tax applied and the total tax is the sum
- [ ] Given a store has NOT configured any tax rates, when a customer checks out, then tax_amount = 0 (backward compatible)
- [ ] Given admin navigates to Settings > Tax, when the page loads, then they can: enable/disable tax, set default tax rate, set per-category tax rates, set tax display (inclusive/exclusive)
- [ ] Given tax is set to "inclusive" (tax included in price), when the customer views a $100 product, then the price shows $100 and the tax is extracted at checkout ($84.75 subtotal + $15.25 tax = $100)
- [ ] Given tax is set to "exclusive" (tax added on top), when the customer views a $100 product, then the checkout shows $100 + $18 tax = $118

**Configuration stored in store_settings**:
```json
{
  "tax_enabled": true,
  "tax_rate": 18.0,
  "tax_display": "exclusive",
  "tax_label": "GST",
  "category_tax_rates": {
    "12": 12.0,
    "15": 18.0
  }
}
```

#### Out of Scope (MVP)
- Region-based tax rates (different rates per state/country)
- Tax API integration (TaxJar, Avalara)
- Tax-exempt customers
- Multiple tax components on invoice (CGST + SGST breakdown)
- Tax reporting/filing exports

---

### 9B-3: Shipping Rate Calculation

**Priority**: P1 — High  
**Affects**: Backend + Admin Panel + Storefront  
**Estimated Effort**: 3–4 weeks  
**Dependencies**: None  
**Assigned To**: Backend Developer + Admin Frontend Dev

#### Problem Statement
Checkout has a flat shipping amount with no real-time rate calculation. Customers see no delivery estimates, no carrier options, and no tracking links. 66% of retailers will offer same/next-day delivery by 2029 (Deloitte).

#### User Stories
1. As a **store admin**, I want to configure shipping rates (flat rate, weight-based, or free above threshold), so that customers are charged appropriately.
2. As a **customer**, I want to see shipping costs before placing my order, so that there are no surprises.
3. As a **store admin**, I want to add a tracking number to shipped orders, so that customers can track their delivery.
4. As a **customer**, I want to see my tracking link on my order page, so that I can check delivery status.

#### Acceptance Criteria

**Shipping Methods (Admin)**:
- [ ] Given admin navigates to Settings > Shipping, when the page loads, then they can configure shipping methods: flat rate (fixed amount per order), weight-based (rate per kg), free shipping above a threshold (e.g., free over $50), local pickup (free, no shipping address required)
- [ ] Given admin enables "free shipping over $50", when a customer has a $60 cart, then shipping shows as "Free" at checkout
- [ ] Given admin enables "free shipping over $50", when a customer has a $30 cart, then the flat rate or weight-based rate is applied

**Checkout Display**:
- [ ] Given shipping methods are configured, when customer reaches the shipping step of checkout, then they see available shipping options with cost and estimated delivery time
- [ ] Given no shipping methods are configured, when customer reaches checkout, then the existing flat shipping amount behavior is preserved (backward compatible)

**Order Tracking**:
- [ ] Given admin edits a shipped order, when they enter a tracking number and carrier name, then the tracking info is saved on the order
- [ ] Given a customer views their order with tracking info, when the order page loads, then they see the carrier name and tracking number (with link if carrier supports it)

**Database Additions**:
```sql
ALTER TABLE orders ADD COLUMN (
    shipping_method VARCHAR(100) NULL,
    tracking_number VARCHAR(255) NULL,
    tracking_carrier VARCHAR(100) NULL,
    tracking_url VARCHAR(500) NULL,
    estimated_delivery_at TIMESTAMP NULL
);

CREATE TABLE shipping_methods (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('flat_rate', 'weight_based', 'free', 'local_pickup') NOT NULL,
    rate DECIMAL(10,2) NULL,
    config JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    INDEX idx_store_id (store_id)
);
```

#### Out of Scope (MVP)
- Real-time carrier API integration (Shiprocket, EasyPost, FedEx)
- Address-based rate calculation (shipping zones)
- Shipping label printing
- Automatic delivery date calculation
- Package dimension-based rates

---

### 9B-4: Analytics & Reporting Dashboard

**Priority**: P1 — High  
**Affects**: Backend + Admin Panel  
**Estimated Effort**: 3–4 weeks  
**Dependencies**: None  
**Assigned To**: Backend Developer (API) + Admin Frontend Dev (Charts/UI)

#### Problem Statement
The admin panel has zero analytics. Store owners cannot see sales trends, top products, conversion rates, or revenue. This is a fundamental expectation of any e-commerce admin and a key reason clients pay for the platform. Analytics directly justify the $199–$499/month pricing tiers.

#### User Stories
1. As a **store admin**, I want to see today's revenue, orders, and average order value, so that I know how my store is performing.
2. As a **store admin**, I want to see revenue trends over time (7d, 30d, 90d), so that I can identify growth patterns.
3. As a **store admin**, I want to see my top 10 best-selling products, so that I can optimize inventory and marketing.
4. As a **store admin**, I want to see customer acquisition trends, so that I can measure marketing effectiveness.
5. As a **store admin**, I want to see low-stock alerts on the dashboard, so that I can reorder before running out.

#### Acceptance Criteria

**Dashboard KPI Cards**:
- [ ] Given I open the admin dashboard, when the page loads, then I see KPI cards for: Total Revenue (today), Orders Count (today), Average Order Value (today), New Customers (today), each showing a comparison to yesterday (↑12% or ↓5%)

**Revenue Chart**:
- [ ] Given I view the revenue chart, when I select "Last 30 Days", then I see a line chart with daily revenue and a trend line
- [ ] Given I view the revenue chart, when I select "Last 7 Days" or "Last 90 Days", then the chart updates to show the selected period
- [ ] Given I hover over a data point on the chart, when the tooltip appears, then I see the exact date and revenue amount

**Top Products**:
- [ ] Given I view the "Top Products" section, when the page loads, then I see the 10 best-selling products by revenue with: product name, units sold, revenue generated, ranked by revenue

**Order Status Breakdown**:
- [ ] Given I view the "Order Status" section, when the page loads, then I see a donut/pie chart showing orders by status (pending, processing, shipped, delivered, cancelled) for the current period

**Recent Activity**:
- [ ] Given I view the dashboard, when I scroll to "Recent Orders", then I see the 10 most recent orders with: order number, customer name, total, status, time ago

**API Endpoints**:
- [ ] `GET /api/v1/admin/analytics/dashboard` → KPIs, revenue chart data, top products, order status breakdown
- [ ] `GET /api/v1/admin/analytics/revenue?period=30d` → revenue by day/week/month
- [ ] `GET /api/v1/admin/analytics/top-products?period=30d&limit=10` → top products by revenue
- [ ] `GET /api/v1/admin/analytics/customers?period=30d` → new vs returning customers

**Tenant Isolation**:
- [ ] All analytics are scoped to the current store_id — admin only sees their own store data

#### Out of Scope (MVP)
- Real-time analytics (updates without page refresh)
- Conversion funnel (visited → cart → checkout → paid)
- Customer lifetime value
- Custom date range picker
- CSV/PDF export
- Google Analytics integration

#### Technical Notes for Tech Lead
- Use raw SQL queries with aggregations for performance — don't load all orders into memory
- Cache dashboard data for 5 minutes (invalidate on new order)
- Chart library: Recharts (already common with React) or Chart.js
- Period parameter: `7d`, `30d`, `90d` — default `30d`

---

### 9B-5: Return & Refund Management (RMA)

**Priority**: P1 — High  
**Affects**: Backend + Admin Panel  
**Estimated Effort**: 2–3 weeks  
**Dependencies**: 9A-4 (Payment Gateway — for automated refunds)  
**Assigned To**: Backend Developer + Admin Frontend Dev

#### Problem Statement
The only refund mechanism is changing order status to "refunded." No RMA workflow, no partial refunds, no return reason tracking. US retailers forecast $849.9B in returns for 2025 (NRF) — a proper return workflow is essential.

#### User Stories
1. As a **customer**, I want to request a return for an item I received, so that I can get a refund or exchange.
2. As a **store admin**, I want to review return requests and approve or reject them, so that I can manage returns efficiently.
3. As a **store admin**, I want to process refunds (full or partial) through the payment gateway, so that customers get their money back without manual intervention.
4. As a **store admin**, I want to see return reasons, so that I can identify product quality issues.

#### Acceptance Criteria

**Return Request (Customer)**:
- [ ] Given I have a "delivered" order, when I click "Request Return" on the order page, then I see a form with: items to return (checkbox per item), return reason (dropdown: defective, wrong item, not as described, changed mind, other), description (text, optional)
- [ ] Given I submit a return request, when the request is created, then I see "Return request submitted. We'll review it within 2 business days." and the return status is "requested"

**Admin Workflow**:
- [ ] Given a return request is submitted, when admin opens Returns page, then they see the request with: order number, customer name, items, reason, date, status, "Approve" / "Reject" buttons
- [ ] Given admin approves a return, when they click "Approve", then the return status changes to "approved" and the customer is notified via email (if 9A-1 complete)
- [ ] Given the returned item is received, when admin marks it as "received", then the return status changes to "received" and the refund can be processed
- [ ] Given admin processes a refund for an approved+received return, when they click "Process Refund", then: if payment was via gateway → refund through Stripe/Razorpay API; if payment was manual → mark as "refund pending" with admin note

**Partial Refunds**:
- [ ] Given an order has 3 items and customer returns 1 item, when admin processes the refund, then only the returned item's amount is refunded (partial refund)
- [ ] Given admin wants to deduct a restocking fee, when they edit the refund amount, then the adjusted amount is refunded and the fee is logged

**Database Schema**:
```sql
CREATE TABLE returns (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    return_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('requested', 'approved', 'rejected', 'received', 'refunded', 'closed') DEFAULT 'requested',
    reason ENUM('defective', 'wrong_item', 'not_as_described', 'changed_mind', 'other') NOT NULL,
    description TEXT NULL,
    items JSON NOT NULL,
    refund_amount DECIMAL(10,2) NULL,
    refund_method VARCHAR(50) NULL,
    admin_notes TEXT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    refunded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_store_id (store_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
);
```

#### Out of Scope (MVP)
- Customer self-service return portal on storefront
- Return shipping labels
- Exchange workflow (return + new order)
- Automated refund rules (auto-approve returns under $20)
- Return analytics/reporting

---

## Sub-Phase 9C: Differentiation (P2)

**Duration**: Weeks 11–18  
**Goal**: Features that differentiate StoreForge from WooCommerce/basic Shopify and justify premium pricing tiers.  
**Exit Criteria**: Platform has rule-based product recommendations, email marketing integration, multi-currency support, and a loyalty program.

---

### 9C-1: AI-Powered Product Recommendations (Rule-Based)

**Priority**: P2 — Medium  
**Affects**: Backend + Storefront  
**Estimated Effort**: 3–4 weeks  
**Dependencies**: Sufficient order history data  
**Assigned To**: Backend Developer + Storefront Frontend Dev

#### Problem Statement
AI-powered personalization drives 10–30% of e-commerce revenue (McKinsey). StoreForge has zero recommendation capability. Starting with rule-based recommendations (co-purchase, same category) provides immediate value without ML infrastructure.

#### Acceptance Criteria
- [ ] Given I view a product page, when the page loads, then I see a "You may also like" section with 4 products from the same category
- [ ] Given I view a product page and the product has order history, when the page loads, then I see a "Frequently bought together" section with products commonly ordered alongside this one
- [ ] Given I view my cart, when the page loads, then I see a "Customers also bought" section based on cart contents
- [ ] `GET /api/v1/storefront/products/{id}/recommendations?type=similar` → same-category products
- [ ] `GET /api/v1/storefront/products/{id}/recommendations?type=bought_together` → co-purchase recommendations

---

### 9C-2: Newsletter & Email Marketing Integration

**Priority**: P2 — Medium  
**Affects**: Backend + Storefront  
**Estimated Effort**: 1–2 weeks  
**Dependencies**: 9A-1 (Transactional Email System)  
**Assigned To**: Backend Developer

#### Acceptance Criteria
- [ ] Given a customer registers and opts in to marketing (`accepts_marketing: true`), when the account is created, then the customer is synced to the configured email marketing platform (Mailchimp/Klaviyo)
- [ ] Given a storefront footer has a newsletter signup form, when a visitor enters their email and submits, then the email is added to the store's marketing list
- [ ] Given admin navigates to Settings > Email Marketing, when the page loads, then they can configure Mailchimp API key and list ID

---

### 9C-3: Multi-Currency Support

**Priority**: P2 — Medium  
**Affects**: Backend + Storefront  
**Estimated Effort**: 2–3 weeks  
**Dependencies**: None  
**Assigned To**: Backend Developer + Storefront Frontend Dev

#### Acceptance Criteria
- [ ] Given a store admin configures multiple currencies (USD, INR, EUR), when they set exchange rates, then the rates are stored and used for price display
- [ ] Given a customer's browser locale is `en-IN`, when they visit the storefront, then prices are displayed in INR with correct formatting (₹1,499.00)
- [ ] Payments are always processed in the store's base currency — display currency is visual only (MVP)

---

### 9C-4: Customer Loyalty & Rewards Program

**Priority**: P2 — Medium  
**Affects**: Backend + Admin Panel + Storefront  
**Estimated Effort**: 3–4 weeks  
**Dependencies**: 9A-5 (Coupon Engine — for point redemption)  
**Assigned To**: Backend Developer + Admin Frontend Dev

#### Acceptance Criteria
- [ ] Given a store admin enables the loyalty program, when they configure it, then they set: points earned per dollar spent, points required for discount, point-to-dollar conversion rate
- [ ] Given a customer completes a "delivered" order for $100, when the order is marked delivered, then the customer earns points (e.g., 100 points at 1pt/$ rate)
- [ ] Given a customer has 500 points, when they check out, then they can redeem points for a discount (e.g., 500 points = $5 off)
- [ ] Given admin views customer profiles, when they open a customer, then they see the customer's point balance and transaction history

---

## Implementation Schedule Summary

| Week | Feature | Team | Deliverable |
|------|---------|------|-------------|
| 1 | 9A-1: Transactional Emails | Backend | 6 email templates, queue system |
| 1–2 | 9A-2: Wishlists | Backend + Storefront | API + heart icon + wishlist page |
| 2–3 | 9A-3: Product Reviews | Backend + Admin + Storefront | Review CRUD + moderation + Schema.org |
| 2–4 | 9A-4: Payment Gateway | Backend + Storefront | Stripe + Razorpay + webhooks |
| 3–4 | 9A-5: Coupon Engine | Backend + Admin + Storefront | Coupon CRUD + checkout integration |
| 5 | 9B-1: Abandoned Cart | Backend | Detection + recovery emails |
| 5–6 | 9B-2: Tax Calculation | Backend | Configurable tax rates |
| 6–8 | 9B-3: Shipping Rates | Backend + Admin | Shipping methods + tracking |
| 7–9 | 9B-4: Analytics Dashboard | Backend + Admin | KPIs + charts + top products |
| 9–10 | 9B-5: Returns & Refunds | Backend + Admin | RMA workflow + gateway refunds |
| 11–12 | 9C-1: Recommendations | Backend + Storefront | Rule-based "also bought" + "similar" |
| 13–14 | 9C-2: Email Marketing | Backend + Storefront | Mailchimp/Klaviyo sync |
| 14–16 | 9C-3: Multi-Currency | Backend + Storefront | Exchange rates + display formatting |
| 16–18 | 9C-4: Loyalty Program | Backend + Admin + Storefront | Points earn/redeem system |

---

## Success Metrics

### Phase 9A Exit Criteria (Must pass ALL to proceed to 9B)
1. A customer can pay by card on Honey Bee and see the order confirmed instantly
2. Order confirmation email is sent automatically on every order
3. Customers can add/remove items from wishlist and view wishlist page
4. Customers can submit product reviews; admin can approve/reject
5. Store admin can create a coupon; customer can apply it at checkout

### Phase 9B Exit Criteria
1. Abandoned carts trigger recovery emails within 1 hour
2. Tax is calculated automatically based on store configuration
3. Shipping rates display at checkout (flat rate minimum)
4. Admin dashboard shows revenue, orders, top products for the last 30 days
5. Admin can process returns and refunds through the payment gateway

### Phase 9C Exit Criteria
1. Product pages show "You may also like" recommendations
2. Newsletter signup form syncs to email marketing platform
3. Prices display in customer's local currency
4. Customers earn and redeem loyalty points

---

## Risk Register

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Stripe/Razorpay KYC delays | Blocks live payment testing | Medium | Start KYC process Week 1 while building integration |
| Email deliverability issues | Emails land in spam | Medium | Use verified domain + SPF/DKIM; start with Mailgun sandbox |
| Scope creep in coupon engine | Delays other features | High | Strict MVP: code-based coupons only, no auto-promotions |
| Analytics queries slow on large datasets | Dashboard timeouts | Low | Pre-aggregate daily summaries; cache 5 minutes |
| Tax compliance complexity | Legal risk for clients | Medium | MVP: flat rate only; document that this is not tax advice |

---

## How to Track Progress

Update [PROGRESS.md](../../PROGRESS.md) after each feature:

```markdown
## Phase 9: Commercial Viability 🚧 IN PROGRESS

### 9A: Commerce Essentials (P0) 🚧
- [ ] 9A-1: Transactional Emails
- [ ] 9A-2: Wishlists
- [ ] 9A-3: Product Reviews & Ratings
- [ ] 9A-4: Payment Gateway (Stripe + Razorpay)
- [ ] 9A-5: Coupon & Promotion Engine

### 9B: Competitive Parity (P1) ⏳
- [ ] 9B-1: Abandoned Cart Recovery
- [ ] 9B-2: Tax Calculation
- [ ] 9B-3: Shipping Rates & Tracking
- [ ] 9B-4: Analytics Dashboard
- [ ] 9B-5: Returns & Refunds (RMA)

### 9C: Differentiation (P2) ⏳
- [ ] 9C-1: AI Recommendations (Rule-Based)
- [ ] 9C-2: Email Marketing Integration
- [ ] 9C-3: Multi-Currency Support
- [ ] 9C-4: Customer Loyalty Program
```

---

## Handoff Checklist

- [x] Feature specs written with acceptance criteria (this document)
- [x] Database schemas defined for new tables
- [x] API endpoint contracts specified
- [x] Dependency map clear
- [ ] **Tech Lead**: Review architecture for 9A-4 (payment gateway abstraction layer)
- [ ] **Backend Developer**: Begin 9A-1 (transactional emails) — lowest effort, highest trust impact
- [ ] **SEO Analyst**: Review 9A-3 (reviews) for AggregateRating Schema.org
- [ ] **Admin Frontend Dev**: Plan moderation UI for 9A-3 (reviews) and CRUD for 9A-5 (coupons)
- [ ] **Storefront Frontend Dev**: Plan wishlist heart icon component and review display section
