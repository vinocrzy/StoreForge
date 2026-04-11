# Honey Bee Storefront — Page Audit & Fix Spec

**Date**: April 11, 2026  
**Auditor**: Product Manager  
**Scope**: `client-honey-bee/` storefront — all pages and navigation links  

---

## Executive Summary

The storefront has **11 pages built**, **10 pages missing (404)**, and **5 pages with dummy/hardcoded data** that would embarrass the client in a demo or live launch. The most critical gaps are: no auth system, hardcoded orders in the account area, and 10 footer/nav links that all 404.

---

## Part 1 — Page Inventory

### ✅ Complete (Real data, functional)

| Page | Route | Status | Notes |
|------|-------|--------|-------|
| Homepage | `/` | ✅ Complete | Fetches API products + categories. Falls back gracefully. |
| Shop All | `/products` | ✅ Complete | `ShopClientShell` fetches from API with filters, pagination, sort |
| Product Detail | `/products/[id]` | ✅ Complete | API wired via `getProductBySlug()`. Hardcoded `PRODUCTS` is fallback-only |
| Our Story | `/our-story` | ✅ Complete | Static brand content — appropriate, no API needed |
| Contact | `/contact` | ✅ Complete | UI complete. Form submit is UI-only (no backend POST yet) |
| Cart | `/cart` | ✅ Complete | `CartContext` API integration, quantity updates, remove items |
| Checkout | `/checkout` | ✅ Complete | Guest checkout fully wired to backend API |
| Order Confirmation | `/orders/confirmation` | ✅ Complete | Reads `?order=` query param, correct post-checkout flow |

---

### ⚠️ Dummy Pages (Built but hardcoded — NOT production-ready)

| Page | Route | Dummy Data Present | Severity |
|------|-------|--------------------|----------|
| Collections Index | `/collections/[slug]` | Entire `COLLECTIONS` object is hardcoded with static products | 🔴 High |
| Account | `/account` | Hardcoded user "Sarah", fake tabs, no auth check | 🔴 High |
| Order History | `/orders` | Hardcoded `ORDERS` array with fake order IDs | 🔴 High |

**Detail on each:**

#### `/collections/[slug]` — Dummy
```
ISSUE: All collection data (ritual, botanical, ayurvedic, sensitive) is hardcoded static objects
       in page.tsx. No API call at all.
RISK:  Changes to real collections in admin panel have zero effect on the storefront.
FIX:   Replace with `getCollectionBySlug(slug)` and `getProductsByCategory()` API calls.
```

#### `/account` — Dummy
```
ISSUE: Shows hardcoded user name "Sarah", fake "Member since 2025" label.
       Three tabs (Orders, Addresses, Preferences) all show placeholder UI.
       No auth check — anyone can visit /account with no login.
       "Sign Out" button does nothing.
RISK:  Client demo will show a stranger's name. No real functionality.
FIX:   Gate with auth, fetch real customer profile, wire Orders tab to API.
       Requires auth system (see Part 2 — Missing Pages).
```

#### `/orders` — Dummy
```
ISSUE: Three hardcoded orders with fake IDs (HB-2026-001, etc.) and placeholder items.
       No loading state, no empty state for real users, no pagination.
RISK:  Shows fake orders to every visitor. Completely misleading.
FIX:   Fetch from `getCustomerOrders()` API. Requires auth.
```

---

### ❌ Missing Pages (Result in 404)

All of these links exist in the UI (Header/Footer) but have **no corresponding route**.

#### Group A — Nav Header Links (User sees 404 clicking nav)

| Missing Page | Route | Where Linked |
|-------------|-------|-------------|
| Collections Index | `/collections` | Header nav "Collections" link |
| Search | `/search` | Header search icon |

#### Group B — Footer "Shop" Column Links

| Missing Page | Route | Where Linked |
|-------------|-------|-------------|
| Rituals | `/rituals` | Footer → Shop → Rituals |
| Ingredients | `/ingredients` | Footer → Shop → Ingredients |

#### Group C — Footer "Learn" Column Links

| Missing Page | Route | Where Linked |
|-------------|-------|-------------|
| The Process | `/process` | Footer → Learn → The Process, also linked from `/our-story` body |
| Journal | `/journal` | Footer → Learn → Journal |

#### Group D — Footer "Customer Service" Column Links

| Missing Page | Route | Where Linked |
|-------------|-------|-------------|
| Shipping | `/shipping` | Footer → Customer Service → Shipping |
| Returns | `/returns` | Footer → Customer Service → Returns |

#### Group E — Footer Bottom Bar Legal Links

| Missing Page | Route | Where Linked |
|-------------|-------|-------------|
| Privacy Policy | `/privacy` | Footer bottom bar |
| Terms of Service | `/terms` | Footer bottom bar |

#### Group F — Auth System (No pages exist, no links in nav yet)

| Missing Page | Route | Impact |
|-------------|-------|--------|
| Login | `/login` or `/auth/login` | Account page has no auth gate |
| Register | `/register` or `/auth/register` | No sign-up flow |

---

## Part 2 — Prioritised Fix Plan

### Priority Tiers

| Priority | Criteria |
|----------|---------|
| **P0 — Must fix before any client demo** | Visible fake data, broken nav links in primary navigation |
| **P1 — Fix before public launch** | Footer 404s, legal pages |
| **P2 — Important but deferrable** | Content pages (Journal, Rituals, Ingredients) |
| **P3 — Post-launch** | Full auth system, account management |

---

### P0 — Critical (Fix Now)

#### P0.1 — Collections Index Page (`/collections`)
**Problem**: Header nav link "Collections" → 404  
**Acceptance Criteria**:
- [ ] Given a user clicks "Collections" in nav, they see a page listing all collections
- [ ] Each collection card links to `/collections/[slug]`
- [ ] Collections are fetched from API (via `getCategories()`)
- [ ] Fallback shows categories grid if API is down
- [ ] Page matches Stitch "Luminous Alchemist" design

#### P0.2 — Collections Detail Page (Replace Dummy Data)
**Problem**: `/collections/[slug]` shows hardcoded static products  
**Acceptance Criteria**:
- [ ] Given a user visits `/collections/floral`, they see only floral collection products
- [ ] Products are fetched from API using `getProductsByCategory(slug)`
- [ ] Page handles invalid slug with `notFound()`
- [ ] Removing hardcoded `COLLECTIONS` object from page.tsx

#### P0.3 — Account Page (Remove Fake User)
**Problem**: Shows hardcoded "Sarah" with fake orders and no auth  
**Two-Option Approach** (pick one based on auth timeline):
- **Option A (Quick fix)**: Show empty/guest state if no auth. Display "Sign In" prompt instead of fake profile. No dummy data.
- **Option B (Full fix)**: Implement login gate — redirect to `/login` if not authenticated, fetch real profile if authenticated.

**Minimum Acceptance Criteria (Option A)**:
- [ ] Account page shows "Sign in to view your account" if not authenticated
- [ ] No hardcoded "Sarah" name or fake orders visible to unauthenticated users
- [ ] Link to login/register (can be simple page or modal)

#### P0.4 — Orders Page (Remove Fake Orders)
**Problem**: Three hardcoded fake orders visible to all visitors  
**Acceptance Criteria**:
- [ ] If user is not authenticated: show "Sign in to view your orders"
- [ ] If user is authenticated: fetch from API (or show empty state if no orders)
- [ ] Hardcoded `ORDERS` array removed

---

### P1 — Before Launch

#### P1.1 — Shipping Info Page (`/shipping`)
**Problem**: Footer link 404s  
**Acceptance Criteria**:
- [ ] Static page explaining shipping rates, timelines, and free shipping threshold ($75)
- [ ] Matches Stitch design system
- [ ] Content: standard US shipping 3-5 days, free over $75, international TBD

#### P1.2 — Returns Policy Page (`/returns`)
**Problem**: Footer link 404s  
**Acceptance Criteria**:
- [ ] Static page with 30-day return policy information
- [ ] Contact form link pointing to `/contact`

#### P1.3 — Privacy Policy Page (`/privacy`)
**Problem**: Footer bottom bar link 404s — legal requirement  
**Acceptance Criteria**:
- [ ] Standard e-commerce privacy policy page
- [ ] Includes data collection, cookies, third-party services

#### P1.4 — Terms of Service Page (`/terms`)
**Problem**: Footer bottom bar link 404s — legal requirement  
**Acceptance Criteria**:
- [ ] Standard e-commerce terms of service
- [ ] Purchase terms, refund policy reference, limitations of liability

#### P1.5 — The Process Page (`/process`)
**Problem**: Footer "Learn" link 404s AND it is linked from the our-story page body  
**Acceptance Criteria**:
- [ ] Static page explaining cold-process soap making steps
- [ ] Reuses `NursePromiseBand` component for brand consistency
- [ ] 4-step process section already designed (info is in `our-story` PROCESS_STEPS constant — can be promoted to its own page)

---

### P2 — Important (Post-Launch Sprint)

#### P2.1 — Search Page (`/search`)
**Problem**: Header search icon links to `/search` → 404  
**Acceptance Criteria**:
- [ ] Search input pre-populated if URL contains `?q=` param
- [ ] Calls `getProducts({ search: q })` API
- [ ] Empty state: "No results for {q} — browse all soaps"
- [ ] Results use same `ProductCard` component

#### P2.2 — Journal / Blog (`/journal`)
**Problem**: Footer link 404s  
**Scope**: MVP is a static placeholder or empty state  
**Acceptance Criteria**:
- [ ] Page exists with "Coming Soon" / "The Ritual Letter" brand copy
- [ ] No 404

#### P2.3 — Rituals Page (`/rituals`)
**Problem**: Footer link 404s  
**Scope**: Static or curated product bundle page  
**Acceptance Criteria**:
- [ ] Page exists, no 404
- [ ] Can be simple static content or redirect to a featured collection

#### P2.4 — Ingredients Index (`/ingredients`)
**Problem**: Footer link 404s  
**Scope**: Glossary of hero ingredients used across the range  
**Acceptance Criteria**:
- [ ] Page exists with ingredient cards (Honey, Shea, Oat, Turmeric, etc.)
- [ ] Can be static content, no API needed

---

### P3 — Post-Launch (Auth System)

#### P3.1 — Login Page (`/login`)
- Phone-first auth per platform standards  
- `POST /api/v1/auth/login` integration  
- Redirect to `/account` on success

#### P3.2 — Register Page (`/register`)
- Name, email, phone fields  
- `POST /api/v1/auth/register` integration  
- Auto-login after registration

#### P3.3 — Real Account Page (replaces P0.3 Option A)
- Real customer profile from `GET /api/v1/customers/me`
- Real order history from `GET /api/v1/orders`
- Address management

---

## Part 3 — Recommended Implementation Order

```
Sprint 1 (This session — P0 fixes):
  1. ✅ /collections  — Add index page (real API data)
  2. ✅ /collections/[slug]  — Replace dummy with API data
  3. ✅ /account  — Remove "Sarah", show guest CTA
  4. ✅ /orders  — Remove fake orders, show guest CTA

Sprint 2 (Before demo — P1):
  5. /shipping  — Static info page
  6. /returns  — Static policy page
  7. /process  — Promote content from our-story
  8. /privacy  — Legal boilerplate
  9. /terms  — Legal boilerplate

Sprint 3 (Pre-launch — P2):
  10. /search  — Search results page
  11. /journal  — Placeholder/coming soon
  12. /rituals  — Static or redirect
  13. /ingredients  — Static glossary

Sprint 4 (Post-launch — P3):
  14. /login  — Auth flow
  15. /register  — Auth flow
  16. /account  — Real customer data
  17. /orders  — Real order history
```

---

## Part 4 — Quick Stats

| Category | Count |
|----------|-------|
| Complete pages | 8 |
| Dummy pages (built but fake data) | 3 |
| Missing pages (404) | 10 |
| **Total pages needed** | **21** |
| **Currently working** | **8 (38%)** |

---

## Decision Required

**Should we proceed to fix?**

Recommended scope for this session: **P0 + P1** (Sprints 1 & 2) — removes all embarrassing dummy data and eliminates all structural 404s before the next client demo.

P2 and P3 (auth, search, content pages) can be separate work items.

---
*Spec authored by Product Manager, April 11, 2026*
