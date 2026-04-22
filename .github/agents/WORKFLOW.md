# StoreForge Multi-Agent Development Workflow

## Team Structure

| Agent | Role | Layer |
|-------|------|-------|
| **Business Analyst** | Market & trend analysis, competitive benchmarking, improvement proposals | Strategy |
| **SEO Analyst** | Technical SEO, keyword research, audits, SEO briefs | Strategy |
| **Product Manager** | Requirements, specs, acceptance criteria, backlog | Planning |
| **Tech Lead** | Architecture, API contracts, technical decisions, delegation | Architecture |
| **Brand Identity Designer** | Brand strategy, color/type systems, design tokens | Design |
| **Backend Developer** | Laravel API, database migrations, services, tests | Implementation |
| **Admin Frontend Dev** | React admin panel (TailAdmin + RTK Query) | Implementation |
| **Storefront Frontend Dev** | Next.js storefronts, UX design, SEO | Implementation |
| **QA & Testing Expert** | Test execution, quality gates, security, accessibility | Quality |
| **DevOps Engineer** | Docker, CI/CD pipelines, deployments, monitoring | Operations |
| **Honey Bee Dev** | Honey Bee client storefront (brand-specific specialist) | Implementation |

---

## Standard Feature Development Workflow

### Phase 0: Strategy & Research (optional — for new initiatives)

```
Business Analyst
├── Scan market trends, competitor moves, customer feedback
├── Build competitive benchmark (StoreForge vs. Shopify/BigCommerce/Medusa/…)
├── Produce ranked improvement proposals with ROI estimates
└── Hand off top proposal → Product Manager
         │
         └── If SEO-relevant → route through SEO Analyst first

SEO Analyst (triggered by BA proposal, audit request, ranking drop, or new storefront)
├── Run technical SEO audit / keyword research / SERP analysis
├── Write SEO brief (metadata, schema, CWV, internal linking, acceptance criteria)
└── Hand off to:
     • Product Manager           → content / URL-structure / new-page initiatives
     • Backend Developer         → SEO DB fields, sitemap logic, API SEO payload
     • Storefront / Honey Bee Dev → generateMetadata, Schema.org, CWV fixes
     • QA & Testing Expert       → SEO acceptance criteria validation
```

### Phase 1: Discovery & Planning

```
Product Manager
├── Receive: stakeholder brief OR Business Analyst proposal OR SEO Analyst brief
├── Gather requirements (client brief, stakeholder interviews)
├── Write feature spec (user stories + acceptance criteria)
├── Define MVP scope vs. future enhancements
├── Map dependencies and integration points
└── Hand off spec → Tech Lead
```

### Phase 2: Architecture & Design (Run in parallel where possible)

```
Tech Lead (receives spec from Product Manager)
├── Assess technical feasibility
├── Design database schema + API contracts
├── Identify multi-tenancy requirements (store_id scoping)
├── Create task breakdown:
│   ├── Backend tasks   → Backend Developer
│   ├── Admin UI tasks  → Admin Frontend Dev
│   └── Storefront tasks → Storefront Frontend Dev
│
└── (New client only) Brand Identity Designer
    ├── Brand discovery (values, audience, competitors)
    ├── Color palette + typography system
    ├── Design tokens + component style guide
    ├── Document in .brand/identity.md
    └── Hand off design system → Storefront Frontend Dev
```

### Phase 3: Development (Parallel tracks)

```
Backend Developer       Admin Frontend Dev       Storefront Frontend Dev
├── Migrations          ├── API service types     ├── Receive design tokens
├── Models              ├── RTK Query services    ├── Scaffold pages (SSG)
├── Services            ├── Redux slices          ├── Connect to API
├── Controllers         ├── TailAdmin components  ├── SEO metadata
├── Feature tests       ├── Form validation       ├── Schema.org markup
└── Scribe API docs     └── Protected routes      └── Core Web Vitals
        │                        │                        │
        └────────────────────────┴────────────────────────┘
                                 │
                     QA & Testing Expert (Phase 4)
```

### Phase 4: Quality Assurance (Gating phase — nothing deploys without this)

```
QA & Testing Expert
├── Run backend tests:     php artisan test
├── Validate tenant isolation: php artisan test --filter=Tenant   ← CRITICAL
├── Run TypeScript build:  npm run build
├── Run ESLint:            npm run lint
├── Accessibility audit:   WCAG 2.1 AA compliance
├── Security review:       OWASP Top 10 scan
├── E2E smoke tests:       critical user flows
│
├── PASS → Hand off to DevOps Engineer
└── FAIL → Return to developer with a specific issue report
```

### Phase 5: Deployment & Operations

```
DevOps Engineer
├── Review deployment checklist
├── Apply environment config changes
├── Deploy to staging → run smoke tests
├── Get sign-off from Tech Lead
├── Deploy to production
├── Monitor logs + performance (Telescope, uptime)
└── Notify Product Manager → feature is live
```

---

## Quick Agent Routing Guide

| Task | Primary Agent | Support |
|------|--------------|---------|
| "Are we keeping up with market trends?" | Business Analyst | Tech Lead |
| Competitive benchmark / feature gap analysis | Business Analyst | Product Manager |
| Business case / ROI for new initiative | Business Analyst | Tech Lead |
| Customer behaviour / funnel analysis | Business Analyst | Product Manager |
| SEO audit / ranking drop investigation | SEO Analyst | Storefront Frontend Dev |
| Keyword research / content strategy | SEO Analyst | Product Manager |
| Schema.org / structured data specification | SEO Analyst | Backend Developer |
| Core Web Vitals diagnosis & prioritisation | SEO Analyst | Storefront Frontend Dev |
| New feature spec / user stories | Product Manager | Tech Lead |
| Architecture or DB schema decision | Tech Lead | — |
| Database migration | Backend Developer | Tech Lead |
| New API endpoint | Backend Developer | Tech Lead |
| Admin panel page or form | Admin Frontend Dev | — |
| New client storefront setup | Storefront Frontend Dev | Brand Identity Designer |
| Brand identity, colors, typography | Brand Identity Designer | — |
| Bug in Laravel API | Backend Developer | QA & Testing Expert |
| TypeScript error in admin panel | Admin Frontend Dev | — |
| Honey Bee UI component | Honey Bee Dev | Storefront Frontend Dev |
| Docker config / infrastructure | DevOps Engineer | — |
| Pre-commit / pre-deploy validation | QA & Testing Expert | — |
| Performance degradation | DevOps Engineer | Tech Lead |
| Security vulnerability | QA & Testing Expert | Tech Lead |
| SEO / sitemap / structured data (implementation) | Storefront Frontend Dev | SEO Analyst |
| SEO / sitemap / structured data (strategy & spec) | SEO Analyst | Backend Developer |

---

## Handoff Checklists

### Business Analyst → Product Manager
- [ ] Improvement proposal saved to `docs/analysis/`
- [ ] Ranked list of options with ROI, effort, and risk
- [ ] Clear "top recommendation" identified
- [ ] Success criteria / target metrics defined
- [ ] Sources cited (market reports, competitor URLs, customer evidence)

### Business Analyst → SEO Analyst (when initiative has SEO dimension)
- [ ] Strategic opportunity described (target market, competitor context)
- [ ] Expected organic-search impact stated
- [ ] Any keyword hypotheses or competitor URLs provided

### SEO Analyst → Product Manager (for content / URL-structure initiatives)
- [ ] SEO brief saved to `docs/seo/`
- [ ] Target URL pattern and primary keyword defined
- [ ] Search intent labelled
- [ ] Content requirements (word count, entities, FAQ candidates) listed

### SEO Analyst → Backend Developer (for DB/API/sitemap changes)
- [ ] Required SEO fields listed (with types and constraints)
- [ ] Sitemap inclusion rules specified
- [ ] API response shape for SEO payload defined

### SEO Analyst → Storefront Frontend Dev / Honey Bee Dev (for metadata / Schema.org / CWV)
- [ ] Exact metadata templates (title, meta, OG, canonical, robots)
- [ ] Exact Schema.org types and required fields
- [ ] Internal linking requirements
- [ ] Core Web Vitals targets (LCP < 2.5s, INP < 200ms, CLS < 0.1)
- [ ] SEO acceptance criteria for QA validation

### Product Manager → Tech Lead
- [ ] Feature spec document written and attached
- [ ] User stories with acceptance criteria defined
- [ ] Priority and deadline agreed upon
- [ ] Known dependencies and blockers documented

### Tech Lead → Backend Developer
- [ ] Database schema diagram or migration spec provided
- [ ] API contract defined (endpoints, request/response shapes, error codes)
- [ ] Multi-tenancy requirements explicitly stated
- [ ] Performance and caching expectations set

### Tech Lead → Frontend Developers
- [ ] API endpoint documentation shared (Scribe link or spec)
- [ ] Auth/authorization requirements clarified
- [ ] Error handling and edge cases specified
- [ ] Responsive/accessibility requirements set

### Brand Identity Designer → Storefront Frontend Dev
- [ ] Color palette (primary, secondary, neutral, accent) with CSS variables
- [ ] Typography scale (font families, weights, sizes) defined
- [ ] Component variants documented (buttons, cards, badges)
- [ ] Design tokens exported to `.brand/tokens.css`
- [ ] `.brand/identity.md` completed and reviewed

### Backend Developer → Frontend Developers
- [ ] All API endpoints implemented and feature-tested
- [ ] Scribe docs regenerated (`php artisan scribe:generate`)
- [ ] Error codes and validation messages documented
- [ ] Tenant-scoped responses verified

### Any Developer → QA & Testing Expert
- [ ] Feature implementation complete
- [ ] Manually tested locally (no console errors)
- [ ] No known regressions
- [ ] Branch name and PR description provided

### QA & Testing Expert → DevOps Engineer
- [ ] All unit and integration tests passing (100%)
- [ ] Tenant isolation tests passing
- [ ] No TypeScript errors (build clean)
- [ ] No critical accessibility issues (WCAG 2.1 AA)
- [ ] No OWASP Top 10 vulnerabilities found
- [ ] Performance benchmarks met (API < 200ms p95)

---

## RACI Matrix

| Activity | BA | SEO | PM | Tech Lead | Brand | Backend | Admin FE | Store FE | QA | DevOps |
|----------|----|-----|----|-----------|-------|---------|----------|----------|----|--------|
| Market trend / competitive analysis | **R** | C | A | C | — | — | — | — | — | — |
| Improvement proposal / business case | **R** | C | A | C | — | I | I | I | — | — |
| SEO audit / keyword strategy | C | **R** | A | I | — | C | — | C | C | — |
| SEO brief (metadata / schema / CWV) | — | **R** | A | I | — | C | — | C | C | — |
| Feature spec | I | C | **R** | C | — | I | I | I | I | — |
| DB schema design | — | C | I | **A** | — | R | I | — | C | — |
| API contract design | — | C | I | **A** | — | R | C | C | C | — |
| Brand identity | C | — | I | I | **R** | — | — | C | — | — |
| Admin UI implementation | — | — | — | C | — | — | **R** | — | A | — |
| Storefront implementation | — | C | — | C | C | — | — | **R** | A | — |
| Backend implementation | — | C | I | C | — | **R** | — | — | A | — |
| Testing & QA (incl. SEO validation) | — | C | I | I | — | C | C | C | **R** | — |
| Deployment | — | — | I | C | — | I | I | I | C | **R** |
| Monitoring (incl. organic traffic, CWV) | C | C | I | C | — | I | — | — | C | **R** |

> R = Responsible · A = Accountable · C = Consulted · I = Informed

---

## Workflow Variations

### New Client Storefront (Full Engagement)

```
1. Product Manager      → gather client brief, sitemap, feature list
2. Brand Identity Designer → brand discovery, color/type system, .brand/ docs
3. Tech Lead            → create tenant in DB, API config, task breakdown
4. Backend Developer    → seed store data, configure store settings
5. Storefront Frontend Dev → scaffold from template, apply brand design system
6. QA & Testing Expert  → full storefront review, accessibility audit
7. DevOps Engineer      → DNS, SSL, CDN, deployment
8. Product Manager      → client acceptance review, sign-off
```

### Backend Feature (API only)

```
1. Tech Lead            → approve schema + API contract
2. Backend Developer    → migration, model, service, controller, tests, Scribe docs
3. QA & Testing Expert  → validate + tenant isolation tests
4. DevOps Engineer      → deploy to production
```

### Admin Panel Feature

```
1. Tech Lead            → approve spec, assign tasks
2. Backend Developer    → API endpoint (if new data needed)
3. Admin Frontend Dev   → UI component / page with RTK Query
4. QA & Testing Expert  → TypeScript build + accessibility + E2E
5. DevOps Engineer      → deploy
```

### Honey Bee Storefront Work

```
1. Product Manager / Client → describe task or design change
2. Honey Bee Dev            → implement using Stitch design system
   ├── Reads HONEY-BEE-DESIGN-SYSTEM.md first
   ├── References Stitch screen.png prototypes
   └── Follows .github/skills/honey-bee-storefront-design/SKILL.md
3. QA & Testing Expert      → SEO, accessibility, performance check
4. DevOps Engineer          → deploy
```

---

## Critical Rules (Non-Negotiable)

| Rule | Owner |
|------|-------|
| All code goes through QA before deployment | QA & Testing Expert |
| Every multi-tenant query must scope by `store_id` | Backend Developer |
| Tech Lead approves all DB schema changes | Tech Lead |
| Brand Identity Designer produces strategy — not code | Brand Identity Designer |
| All storefronts must pass WCAG 2.1 AA | QA & Testing Expert |
| DevOps owns all production changes — no direct developer deploys | DevOps Engineer |
| Phone is the primary auth method; phone field is always required | Backend Developer |
| All API endpoints must have Scribe annotations + updated API-REFERENCE.md | Backend Developer |
| PROGRESS.md must be updated after every significant milestone | All agents |
