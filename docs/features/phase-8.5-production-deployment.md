# Phase 8.5: Production Deployment — honeybee.net.in

**Feature ID**: PHASE-8.5  
**Priority**: P0 — Critical (Revenue blocker)  
**Affects**: Backend, Storefront (Netlify), DNS/Cloudflare  
**Dependencies**: Phase 8.4 complete ✅ (Go/No-Go pending manual tests + Lighthouse)  
**Estimated Effort**: 1–2 days  
**Created**: April 22, 2026  
**Target Launch**: April 24, 2026  
**Status**: ⏳ Ready to Start (awaiting Go/No-Go)

---

## Problem Statement

The Honey Bee platform is feature-complete and 90% production-ready (Phase 8.4 done).
The storefront is deployed nowhere — customers cannot shop. This blocks all revenue.

**Goal**: Deploy backend API + Honey Bee storefront to live domains so Honey Bee can start taking orders.

---

## Architecture Overview

```
[Customer Browser]
      │
      ▼
honeybee.net.in  ──►  Netlify (Next.js storefront)
                              │
                              ▼ HTTPS API calls
api.honeybee.net.in  ──►  Cloudflare Tunnel ──► Home Server (Docker)
                                                  ├── Laravel (PHP 8.2)
                                                  ├── MySQL
                                                  └── Redis
```

---

## User Stories

- As a Honey Bee customer, I want to visit honeybee.net.in and shop, so I can buy products.
- As the store owner, I want orders flowing into the admin panel, so I can fulfil them.
- As the platform PM, I want the deployment to be repeatable, so future client launches take hours not days.

---

## Acceptance Criteria

### Storefront (Netlify)
- [ ] `honeybee.net.in` loads the Honey Bee homepage in production
- [ ] Netlify build succeeds from `client-honey-bee/` with correct env vars
- [ ] `NEXT_PUBLIC_API_URL` points to `https://api.honeybee.net.in/api/v1`
- [ ] `NEXT_PUBLIC_STORE_ID` = `2`
- [ ] SSL certificate active (HTTPS enforced, HTTP redirects to HTTPS)
- [ ] All 26 routes return 200 (no broken pages)
- [ ] No `localhost` references in production build

### Backend API
- [ ] `https://api.honeybee.net.in/api/v1/public/products` returns live product JSON
- [ ] Cloudflare tunnel is stable (uptime > 99%)
- [ ] CORS allows `https://honeybee.net.in` (no cross-origin errors)
- [ ] Laravel `.env` set to `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL=https://api.honeybee.net.in`

### Guest Checkout (Critical Path)
- [ ] Customer can add to cart on production domain
- [ ] Guest checkout completes end-to-end (real order created in DB)
- [ ] Order appears in admin panel at `admin.honeybee.net.in` (or local admin)

### Security
- [ ] `.env` file not publicly accessible
- [ ] `APP_DEBUG=false` in production
- [ ] Security headers present (X-Frame-Options, CSP, etc.) — already in netlify.toml ✅
- [ ] No API keys or secrets in storefront build

---

## Out of Scope (Phase 8.5)

- Customer email notifications (post-launch)
- Google Analytics / Sentry integration (Phase 9)
- Admin panel deployment to `admin.honeybee.net.in` (local admin is acceptable for MVP)
- Payment gateway integration (manual payment flow is MVP)

---

## Task Breakdown

### Task 1: Backend Production Config (1–2 hours) `[Backend Dev]`

**1.1 Update Laravel `.env` for production**:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.honeybee.net.in

# CORS — allow storefront domain
CORS_ALLOWED_ORIGINS=https://honeybee.net.in,https://www.honeybee.net.in
```

**1.2 Verify CORS config** (`config/cors.php`):
```php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Accept', 'Authorization', 'X-Store-ID'],
```

**1.3 Run production optimisation commands**:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

**1.4 Verify Cloudflare tunnel is running** and pointing to `http://localhost:8000`

**Acceptance**: `curl https://api.honeybee.net.in/api/v1/public/products -H "X-Store-ID: 2"` returns JSON

---

### Task 2: Storefront Netlify Deployment (1–2 hours) `[Frontend Dev / DevOps]`

**2.1 Create Netlify site** (if not already):
- Connect to `client-honey-bee/` folder in repo
- Or: `netlify deploy --dir=client-honey-bee/.next --prod`

**2.2 Set production environment variables in Netlify dashboard**:
```
NEXT_PUBLIC_API_URL     = https://api.honeybee.net.in/api/v1
NEXT_PUBLIC_STORE_ID    = 2
NEXT_PUBLIC_STORE_NAME  = Honey Bee
```

**2.3 Fix `netlify.toml`** — redirect rule is wrong for Next.js SSR:

Current (incorrect for Next.js):
```toml
[[redirects]]
  from = "/*"
  to = "/index.html"   ← SPA redirect, breaks Next.js SSR routes
  status = 200
```

Replace with Next.js plugin:
```toml
[build]
  command = "npm run build"
  publish = ".next"

[[plugins]]
  package = "@netlify/plugin-nextjs"
```

Install plugin:
```bash
cd client-honey-bee
npm install -D @netlify/plugin-nextjs
```

**2.4 Trigger deploy** and verify all 26 routes accessible

**Acceptance**: `https://honeybee.net.in` loads homepage, `https://honeybee.net.in/products` loads product list

---

### Task 3: DNS Configuration (30 min) `[DevOps]`

**Domains to configure**:
| Record | Type | Value |
|--------|------|-------|
| `honeybee.net.in` | CNAME | Netlify app URL |
| `www.honeybee.net.in` | CNAME | Netlify app URL |
| `api.honeybee.net.in` | CNAME | Cloudflare tunnel endpoint |

**3.1** In domain registrar (GoDaddy or Cloudflare): add/update DNS records above  
**3.2** Enable HTTPS in Netlify dashboard (auto-SSL via Let's Encrypt)  
**3.3** Verify SSL propagation (can take up to 24 hours)

---

### Task 4: Smoke Testing on Production (30 min) `[QA / PM]`

Run these tests on the **live production domain** (not localhost):

| # | Test | Expected |
|---|------|----------|
| 1 | Visit `https://honeybee.net.in` | Homepage loads, products visible |
| 2 | Add product to cart | Cart badge updates |
| 3 | Refresh page | Cart persists |
| 4 | Complete guest checkout | Order confirmation page loads |
| 5 | Check admin panel | New order visible |
| 6 | Visit `/products` | Product list loads from API |
| 7 | Visit `/collections/[slug]` | Collection page loads |
| 8 | Visit `/invalid-route` | Custom 404 page shows |

**Pass criteria**: All 8 tests pass → **LAUNCH COMPLETE** ✅

---

### Task 5: Post-Launch Monitoring Setup (1 hour) `[DevOps]` — Optional for Day 1

- [ ] Set up UptimeRobot monitor on `https://honeybee.net.in` (free)
- [ ] Set up UptimeRobot monitor on `https://api.honeybee.net.in` (free)
- [ ] Add Google Analytics 4 tracking ID to storefront env vars

---

## Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| CORS errors block API calls | Medium | High | Verify `CORS_ALLOWED_ORIGINS` before deploy |
| Cloudflare tunnel drops | Low | Critical | Set tunnel to auto-restart; test uptime first |
| Netlify build fails with prod env vars | Low | Medium | Test build locally with prod env vars first |
| DNS propagation delay | Medium | Low | Use Netlify's subdomain first; switch DNS after propagation |
| `netlify.toml` SPA redirect breaks Next.js routes | High | High | Fix redirect + install `@netlify/plugin-nextjs` (Task 2.3) |

---

## Notes for Tech Lead

1. **Critical fix needed before deploy**: `netlify.toml` has a SPA-style redirect (`/* → /index.html`) which will break all Next.js dynamic routes in production. Must be replaced with `@netlify/plugin-nextjs`. This is a deployment blocker.

2. **Backend is currently SQLite in dev** — confirm production uses MySQL before launch. Check `platform/backend/.env` DB connection.

3. **No email notifications** exist yet — customers will not receive order confirmation emails post-launch. Inform the Honey Bee client before launch. This is a known gap.

4. **Admin panel access**: Admin panel is not deployed. Store owner will need VPN or local access to manage orders at launch. Plan admin deployment as Phase 9 task.

---

## Definition of Done

- [ ] `https://honeybee.net.in` live and loading
- [ ] Guest checkout working on production
- [ ] Order created in database
- [ ] No CORS errors in browser console
- [ ] SSL active (green padlock)
- [ ] Smoke test all 8 points pass
- [ ] PROGRESS.md updated with Phase 8.5 complete
