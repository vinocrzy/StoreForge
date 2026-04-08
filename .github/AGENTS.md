# Custom Agents for E-Commerce Platform

This project uses specialized AI agents for different roles and responsibilities. Each agent has specific expertise and tools.

## How to Use Agents

### In GitHub Copilot Chat

1. Click the agent selector (icon with person) in the chat input
2. Choose the appropriate agent for your task
3. Describe what you need
4. The agent will work within their specialized role

### Via Subagent Delegation

The Tech Lead agent can automatically delegate to specialist agents based on the task.

---

## Available Agents

### 1. � Brand Identity Designer

**File**: `.github/agents/brand-identity-designer.agent.md`

**Role**: Senior UI/UX Designer & Brand Strategist

**Use for:**
- **Creating brand identities** for new client storefronts
- **Designing color palettes** with WCAG accessibility
- **Selecting typography** and font pairings
- **Building design systems** (tokens, component variants)
- **Reviewing visual designs** for brand consistency
- **Conducting competitive analysis** and market research
- **Creating .brand/ documentation** (identity, colors, typography, style guide)
- **Ensuring accessibility compliance** (WCAG 2.1 AA)

**Expertise:**
- **Brand Strategy**: Brand essence, positioning, personality
- **Color Theory**: Palette creation, psychology, WCAG contrast
- **Typography**: Font pairing, hierarchy, readability
- **Design Systems**: Tokens, components, style guides
- **UX Research**: Personas, competitive analysis
- **Accessibility**: WCAG 2.1 AA/AAA, inclusive design
- **E-Commerce Design**: Conversion optimization, product pages

**Key Deliverables:**
- `.brand/identity.md` - Brand essence, values, personality, target audience
- `.brand/color-palette.md` - Color system with WCAG testing
- `.brand/typography.md` - Font selection and hierarchy
- `.brand/style-guide.md` - Visual guidelines and patterns
- `.brand/competitive-analysis.md` - Market research

**Example Tasks:**
- **"Create brand identity for new jewelry store"**
- **"Design color palette for organic skincare brand with WCAG testing"**
- **"Select typography that reinforces luxury fashion brand voice"**
- **"Review Honey Bee storefront design for brand consistency"**
- **"Conduct competitive analysis for tech gadgets market"**
- **"Build design system documentation for handmade crafts brand"**

**Tools**: Read-only (reviews designs, creates documentation, no code editing)

---

### 2. 🏗️ Tech Lead

**File**: `.github/agents/tech-lead.agent.md`

**Role**: Technical Manager & System Architect

**Use for:**
- Making architectural decisions
- Planning features across backend/frontend
- Designing database schemas
- Coordinating between teams
- Technology selection
- System design reviews
- Breaking down large features into tasks

**Expertise:**
- System architecture & multi-tenancy
- Laravel 11, React 19, Next.js 14
- MySQL 8, Redis 7, Docker
- API design & scalability

**Key Responsibilities:**
- High-level architecture decisions
- Feature planning and task delegation
- Technical strategy and standards
- Team coordination
- Documentation of decisions

**Example Tasks:**
- "Plan a discount code feature across backend and frontend"
- "Design database schema for product reviews"
- "Review multi-tenant security for new feature"
- "Architect payment gateway integration"

---

### 3. 🔧 Backend Developer

**File**: `.github/agents/backend-developer.agent.md`

**Role**: Senior Laravel Backend Developer

**Use for:**
- Creating Laravel models, services, controllers
- Building API endpoints
- Writing database migrations
- Implementing business logic
- Writing backend tests
- Queue jobs and background tasks

**Expertise:**
- Laravel 11 (Eloquent, Service Container)
- API development (REST)
- Multi-tenancy with global scopes
- MySQL database design
- PHPUnit testing
- Redis caching & queuing

**Key Patterns:**
- Models extend `TenantModel`
- Business logic in service classes
- Controllers delegate to services
- Form Requests for validation
- Scribe annotations for API docs

**Example Tasks:**
- "Create Product model with migrations and API endpoints"
- "Implement discount code validation service"
- "Add inventory tracking with stock alerts"
- "Write tests for order creation flow"

---

### 4. 🎨 Admin Frontend Developer

**File**: `.github/agents/admin-frontend-dev.agent.md`

**Role**: Senior React Frontend Developer (Admin Panel)

**Use for:**
- Building admin panel pages
- Creating forms and dashboards
- Implementing React components
- State management with Redux
- API integration with RTK Query
- TypeScript types and interfaces

**Expertise:**
- React 19 with TypeScript 6
- Redux Toolkit 2.11 & RTK Query
- TailAdmin design system
- Vite 8 build tool
- React Router 7
- Form validation

**Key Patterns:**
- Use TailAdmin components (not Ant Design)
- RTK Query for all API calls
- Type-only imports: `import { type X } from 'y'`
- React Hook Form for forms
- Protected routes for auth pages

**Example Tasks:**
- "Create product management page with form"
- "Build dashboard with sales statistics"
- "Implement order list with filtering"
- "Add customer management interface"

---

### 4. 🛍️ Storefront Frontend Developer & UI/UX Designer

**File**: `.github/agents/storefront-frontend-dev.agent.md`

**Role**: Senior Next.js Frontend Developer & UI/UX Designer (Storefronts)

**Use for:**
- Building customer-facing store pages
- **Creating custom brand identities and design systems**
- **Designing unique visual experiences per client**
- Creating e-commerce UI (products, cart, checkout)
- **Color palette design and typography selection**
- Theme customization per client
- SEO optimization
- **Accessibility compliance (WCAG 2.1 AA)**
- Performance optimization
- Image handling

**Expertise:**
- **UI/UX Design & Brand Identity**
- **Design Systems (custom component libraries per brand)**
- **Color Theory (palettes, contrast, accessibility)**
- **Typography (font pairing, hierarchy, readability)**
- Next.js 14 (App Router, SSG/SSR)
- React 19 (Server/Client Components)
- E-commerce UI patterns
- SEO & structured data
- Theme system customization
- **Responsive Design (mobile-first, adaptive layouts)**
- **Accessibility (WCAG 2.1 AA, keyboard navigation, screen readers)**
- Core Web Vitals optimization

**Key Design Capabilities:**
- Brand discovery and competitive analysis
- Custom color palette creation with proper contrast ratios
- Typography system design with intentional font pairings
- Design token systems (spacing, radius, shadows)
- Component variant design per brand personality
- Style guide documentation
- Accessibility testing and compliance

**Key Patterns:**
- Static generation (SSG) for product pages
- Dynamic metadata for SEO
- Schema.org structured data
- **Design system with brand-specific tokens**
- **Custom component variants per brand**
- Theme config per client
- Next/Image for optimization
- **WCAG 2.1 AA accessibility compliance**

**Example Tasks:**
- **"Design complete brand identity for new jewelry store"**
- **"Create color palette for organic skincare brand"**
- **"Build design system with custom components for tech gadgets store"**
- **"Select typography that reinforces luxury fashion brand voice"**
- "Create product detail page with SSG"
- "Customize theme for Honey Bee store (natural, handmade feel)"
- "Build shopping cart with quantity controls"
- "Implement checkout flow (multi-step)"
- "Add SEO meta tags and structured data"
- **"Ensure WCAG 2.1 AA accessibility compliance"**
- **"Design micro-interactions for add-to-cart experience"**

---

### 6. 🧪 QA & Testing Expert

**File**: `.github/agents/qa-testing-expert.agent.md`

**Role**: Senior QA Engineer & Test Automation Specialist

**Use for:**
- **Pre-commit validation** (running all tests before git push)
- **Testing multi-tenant isolation** (CRITICAL - prevents data leakage)
- **Running test suites** (PHPUnit, Jest, React Testing Library)
- **Accessibility audits** (WCAG 2.1 AA compliance)
- **Security testing** (SQL injection, XSS, CSRF, auth)
- **API contract validation** (response structure, status codes)
- **Performance testing** (API response times, N+1 queries)
- **E2E testing** (checkout flows, admin workflows)
- **Mobile testing** (responsive design, touch targets)
- **Ensuring quality before deployment**

**Expertise:**
- **Test Automation**: PHPUnit, Jest, React Testing Library, Playwright
- **Multi-Tenant Testing**: Tenant isolation, data leakage prevention (CRITICAL)
- **Accessibility Testing**: WCAG 2.1 AA/AAA, screen readers, keyboard navigation
- **Security Testing**: OWASP Top 10, SQL injection, XSS, CSRF
- **API Testing**: Contract testing, REST validation
- **Performance Testing**: Load testing, API benchmarks, Core Web Vitals
- **E2E Testing**: User flows, checkout processes
- **Mobile Testing**: Responsive design, touch accessibility

**Key Responsibilities:**
1. **Pre-Commit Validation** (MANDATORY before every git commit):
   - Run backend tests: `php artisan test`
   - Run type check: `npm run build`
   - Run linter: `npm run lint`
   - Run frontend tests: `npm test`
   - Validate tenant isolation: `php artisan test --filter=Tenant`
   
2. **Multi-Tenant Isolation Testing** (CRITICAL):
   - Test that Store 1 cannot see Store 2's data
   - Test X-Store-ID header validation
   - Test models have global tenant scopes
   - Prevent data leakage across tenants
   
3. **Accessibility Compliance** (WCAG 2.1 AA):
   - Color contrast testing (4.5:1 minimum)
   - Keyboard navigation testing
   - Screen reader compatibility
   - Touch target minimum 44x44px
   
4. **Security Testing**:
   - Authentication & authorization tests
   - SQL injection prevention
   - XSS prevention (HTML escaping)
   - CSRF token validation
   - Rate limiting tests
   
5. **Performance Testing**:
   - API response time < 200ms (p95)
   - Database query optimization (no N+1)
   - Lighthouse performance score 90+
   - Core Web Vitals monitoring

**Test Coverage Standards**:
- Critical paths (auth, checkout): 95%+
- Business logic (services): 90%+
- Controllers: 85%+
- Models: 80%+
- **Tenant isolation**: 100% (NO FAILURES ALLOWED)

**Critical Testing Rules**:
- ✅ **ALWAYS run ALL tests before EVERY commit** (no exceptions)
- ✅ **ALWAYS test tenant isolation for multi-tenant features** (security critical)
- ✅ **ALWAYS test accessibility for UI changes** (WCAG AA required)
- ❌ **NEVER commit code with failing tests**
- ❌ **NEVER skip tenant isolation tests** (data leakage risk)
- ❌ **NEVER ignore TypeScript errors**

**Example Tasks**:
- **"Run pre-commit validation for product reviews feature"**
- **"Test tenant isolation for new order management feature"**
- **"Validate WCAG AA accessibility for checkout page"**
- **"Test API contract for products endpoint matches documentation"**
- **"Check for N+1 query problems in product listing"**
- **"Run security audit on authentication flow"**
- **"Test mobile responsiveness at 320px viewport"**
- **"Verify all tests pass before deploying to production"**

**Tools**: Read, search, run tests, get errors - Can execute test commands but cannot edit code

---

## Agent Coordination

### New Client Storefront Workflow

**Phase 1: Brand Identity & Design** (Led by Brand Identity Designer)
1. **Brand Discovery** → **Brand Identity Designer**
   - Conducts client interview (values, audience, personality)
   - Researches competitors
   - Creates `.brand/identity.md` and `.brand/competitive-analysis.md`

2. **Color System Design** → **Brand Identity Designer**
   - Designs color palette based on brand essence
   - Tests WCAG AA contrast ratios
   - Creates `.brand/color-palette.md`

3. **Typography Selection** → **Brand Identity Designer**
   - Selects fonts matching brand personality
   - Creates type scale and hierarchy
   - Creates `.brand/typography.md`

4. **Style Guide** → **Brand Identity Designer**
   - Documents visual guidelines
   - Defines component patterns
   - Creates `.brand/style-guide.md`

5. **Design System Handoff** → **Brand Identity Designer** → **Storefront Frontend Developer**
   - Reviews design system approach
   - Approves component variant strategy
   - Ensures accessibility compliance

**Phase 2: Implementation** (Led by Storefront Frontend Developer)
6. **Design System Implementation** → **Storefront Frontend Developer**
   - Converts design tokens to code (`src/design-system/tokens/`)
   - Creates component variants (`src/design-system/components/`)
   - Builds React components using design system
   - Implements pages with SSG, SEO, accessibility

**Phase 3: Quality Assurance** (Led by QA & Testing Expert)
7. **Pre-Commit Validation** → **QA & Testing Expert**
   - Runs all backend tests (`php artisan test`)
   - Runs type check (`npm run build`)
   - Runs linter (`npm run lint`)
   - Tests tenant isolation (CRITICAL)
   - Validates accessibility (WCAG AA)
   - Checks mobile responsiveness
   
8. **Quality Gate** → **QA & Testing Expert**
   - ✅ ALL tests pass → Approve for commit
   - ❌ ANY test fails → Block commit, report issues to developer
   - Provides detailed test report with pass/fail results

### Feature Development Workflow

1. **User** has a feature request → **Tech Lead**
2. **Tech Lead** plans architecture → delegates tasks:
   - Database changes → **Backend Developer**
   - Admin UI → **Admin Frontend Developer**
   - Storefront UI (if design needed) → **Brand Identity Designer** then **Storefront Frontend Developer**
   - Storefront UI (implementation only) → **Storefront Frontend Developer**
3. Specialists implement their parts
4. **QA & Testing Expert** validates implementation (**MANDATORY before commit**)
5. **Tech Lead** reviews integration (only if all tests pass)

### Example: Adding Product Reviews

**Tech Lead decides:**
- Database: `product_reviews` table with ratings
- Backend: API endpoints for CRUD operations
- Admin: Review moderation interface
- Storefront: Display reviews with brand-appropriate styling

**Backend Developer creates:**
- Migration for `product_reviews` table
- `ProductReview` model with `TenantModel`
- `ReviewService` for business logic
- API controller with Scribe docs
- Feature tests

**Admin Frontend Developer builds:**
- Reviews management page (TailAdmin components)
- Approve/reject modal
- Filtering by rating/status
- RTK Query hooks

**Brand Identity Designer reviews:**
- Reviews component should match brand personality
- Star rating colors match color palette
- Typography hierarchy clear
- Review form accessible (WCAG AA)

**Storefront Frontend Developer implements:**
- Review list component using design system tokens
- Star rating display with brand colors
- Review submission form with validation
- Schema.org Review markup for SEO
- Accessibility: keyboard navigation, screen reader labels
- Star rating display with accessible labels
- Review submission form with validation
- Schema.org Review markup
- **Accessibility testing: keyboard navigation, screen reader compatibility**

**QA & Testing Expert validates (MANDATORY before commit):**
- **Backend tests**: All ProductReview tests pass
- **Tenant isolation**: Store 1 cannot see Store 2's reviews (CRITICAL)
- **API contract**: Response structure matches documentation
- **Validation tests**: Required fields validated, invalid data rejected
- **Security**: SQL injection prevention, XSS escaping
- **Frontend tests**: Review component renders correctly
- **Accessibility**: WCAG AA compliant (color contrast, keyboard navigation, screen reader)
- **Mobile**: Touch targets minimum 44x44px, responsive at 320px
- **Performance**: API response < 200ms, no N+1 queries
- **Test Report**: ✅ ALL PASS → Approve for commit / ❌ ANY FAIL → Block commit

**Git Commit (only if QA approves)**:
```bash
# QA & Testing Expert runs:
php artisan test  # ✅ 56/56 passed
npm run build     # ✅ No TypeScript errors
npm run lint      # ✅ No ESLint errors
php artisan test --filter=Tenant  # ✅ 100% pass (CRITICAL)

# Developer can now commit:
git add .
git commit -m "feat: Add product reviews with ratings"
git push
```

---

## Tools & Restrictions

### Brand Identity Designer
- **Tools**: read, search, view_image, askQuestions (read-only)
- **Focus**: Brand identity, design systems, color theory, typography, accessibility
- **Creates**: `.brand/` documentation (identity, colors, typography, style guide)
- **No**: Code editing, implementation (reviews and approves designs only)

### Tech Lead
- **Tools**: read, search, web, agent (can delegate)
- **Focus**: Architecture, planning, coordination
- **No**: Direct implementation (delegates to specialists)

### Backend Developer
- **Tools**: read, edit, search, execute
- **Focus**: Laravel models, services, API, tests
- **No**: Frontend code, infrastructure setup

### Admin Frontend Developer
- **Tools**: read, edit, search, execute
- **Focus**: React admin panel, TypeScript, TailAdmin
- **No**: Storefront code, backend API implementation

### Storefront Frontend Developer
- **Tools**: read, edit, search, execute
- **Focus**: Next.js storefronts, design system implementation, SEO, themes
- **No**: Admin panel code, backend API (works with brand identity docs from Designer)

### QA & Testing Expert
- **Tools**: read, search, list_dir, get_errors, run_in_terminal (test execution only)
- **Focus**: Pre-commit validation, multi-tenant isolation testing, accessibility audits, security testing, performance testing
- **Critical Tests**: Tenant isolation (100% pass required), TypeScript compilation, accessibility (WCAG AA)
- **Can Execute**: Test commands (`php artisan test`, `npm run build`, `npm run lint`)
- **No**: Code editing, implementation (validates only, reports issues to developers)
- **Quality Gate**: ❌ If ANY test fails → Code CANNOT be committed (no exceptions)

---

## Best Practices

### 1. Choose the Right Agent

**Use Tech Lead when:**
- Planning new features
- Making architectural decisions
- Need coordination across teams
- Unsure which specialist to use

**Use Specialists when:**
- Clear, focused task in their domain
- Implementation work
- Building specific components
- Writing tests

### 2. Provide Context

Good prompts include:
- What feature/problem
- Which store (if client-specific)
- Any constraints
- Expected outcome

Example:
> "Create a discount code feature for the backend API. Need to support percentage and fixed amount discounts. Should validate code exists, is active, and hasn't exceeded usage limits."

### 3. Review Agent Output

Check:
- Does it follow project patterns?
- Multi-tenant security maintained?
- Tests included?
- Documentation updated?
- Follows coding standards?

---

## Project-Specific Conventions

### Multi-Tenancy (CRITICAL)

**Every agent must respect tenant isolation:**

- Backend: All models scope by `store_id`
- Admin: Pass `X-Store-ID` header in requests
- Storefront: Configure `NEXT_PUBLIC_STORE_ID` per client

### Code Standards

- **Backend**: PSR-12, Service pattern, Scribe docs
- **Admin**: TypeScript strict mode, TailAdmin components
- **Storefront**: Next.js best practices, SEO-first

### Testing Requirements

- Backend: Feature tests with tenant isolation checks
- Admin: Component tests (React Testing Library)
- Storefront: E2E tests for critical flows

---

## Documentation

Key docs for agents:
- [System Architecture](../docs/01-system-architecture.md)
- [Backend Architecture](../docs/02-backend-architecture.md)
- [Database Schema](../docs/03-database-schema.md)
- [API Design](../docs/04-api-design.md)
- [Multi-Tenancy](../docs/07-multi-tenancy.md)
- [Admin Panel Design](../docs/19-admin-panel-design-system.md)
- [SEO Implementation](../docs/17-seo-implementation.md)
- [API Reference](../docs/API-REFERENCE.md)

---

## Adding New Agents

To create a new specialized agent:

1. Create `.github/agents/agent-name.agent.md`
2. Define role, expertise, and tools
3. Document patterns and constraints
4. Add to this AGENTS.md file
5. Test with sample task

See [Agent Customization Skill](../copilot-instructions.md) for syntax.

---

## Support

Questions about agents?
- Check agent's `.agent.md` file for patterns
- Review project documentation
- Ask Tech Lead agent for guidance

---

**Remember**: Agents are specialized tools. Choose the right agent for the job, provide clear context, and review outputs for quality and consistency.
