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

### 1. 🏗️ Tech Lead

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

### 2. 🔧 Backend Developer

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

### 3. 🎨 Admin Frontend Developer

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

## Agent Coordination

### Typical Workflow

1. **User** has a feature request → **Tech Lead**
2. **Tech Lead** plans architecture → delegates tasks:
   - Database changes → **Backend Developer**
   - Admin UI → **Admin Frontend Developer**
   - Storefront UI → **Storefront Frontend Developer**
3. Specialists implement their parts
4. **Tech Lead** reviews integration

### Example: Adding Product Reviews

**Tech Lead decides:**
- Database: `product_reviews` table with ratings
- Backend: API endpoints for CRUD operations
- Admin: Review moderation interface
- Storefront: Display reviews, submit form

**Backend Developer creates:**
- Migration for `product_reviews` table
- `ProductReview` model with `TenantModel`
- `ReviewService` for business logic
- API controller with Scribe docs
- Feature tests

**Admin Frontend Developer builds:**
- Reviews management page
- Approve/reject modal
- Filtering by rating/status
- RTK Query hooks

**Storefront Frontend Developer implements:**
- **Brand discovery: questionnaire about values, audience, competitors**
- **Color palette design: honey gold (#F59E0B), natural green (#10B981), warm cream (#FFFBEB)**
- **Typography selection: Playfair Display (serif headings) + Inter (sans-serif body)**
- **Component library: custom product cards with soft rounded corners**
- Review list on product page
- Star rating display with accessible labels
- Review submission form with validation
- Schema.org Review markup
- **Accessibility testing: keyboard navigation, screen reader compatibility**

---

## Tools & Restrictions

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
- **Focus**: Next.js storefronts, SEO, themes
- **No**: Admin panel code, backend API

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
