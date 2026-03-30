# System Architecture

## Overview

This e-commerce platform is designed as a **multi-tenant SaaS solution** where multiple stores can operate independently from a single backend infrastructure, while sharing common resources and business logic.

## Architecture Pattern

**Microservices-Ready Modular Monolith**

We start with a well-structured monolithic Laravel application that can be split into microservices as scaling demands increase. This provides:
- Faster initial development
- Lower operational complexity
- Clear module boundaries for future extraction
- Cost-effective scaling

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                             │
├──────────────────────────────┬──────────────────────────────────┤
│      Admin Panel (React)     │   Storefront (Next.js SSG)       │
│      - SPA Dashboard         │   - Static Site Generation       │
│      - Real-time Updates     │   - ISR for Dynamic Data         │
└──────────────┬───────────────┴──────────────┬───────────────────┘
               │                               │
               │         API Gateway / CDN     │
               │              (CloudFlare)     │
               │                               │
               └───────────────┬───────────────┘
                               │
┌──────────────────────────────▼───────────────────────────────────┐
│                     APPLICATION LAYER                             │
│                    Laravel API Backend                            │
├───────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Auth &     │  │   Store      │  │   Product    │          │
│  │   Tenant     │  │   Management │  │   Catalog    │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
│                                                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Order      │  │   Inventory  │  │   Promotion  │          │
│  │   Processing │  │   Management │  │   Engine     │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
│                                                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Payment    │  │   Analytics  │  │   Notification│          │
│  │   Integration│  │   & Reporting│  │   Service     │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
└───────────────────────────────────────────────────────────────────┘
                               │
┌──────────────────────────────▼───────────────────────────────────┐
│                        DATA LAYER                                 │
├───────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   MySQL/     │  │   Redis      │  │   S3/MinIO   │          │
│  │   PostgreSQL │  │   Cache      │  │   Storage    │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
│                                                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Queue      │  │   Search     │  │   CDN        │          │
│  │   (Redis)    │  │   (Optional) │  │   Storage    │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
└───────────────────────────────────────────────────────────────────┘
```

## Component Responsibilities

### 1. Admin Panel (React SPA)
**Technology**: React 18+, TypeScript, Redux Toolkit, React Query, Ant Design/Material-UI

**Responsibilities**:
- Store configuration and management
- Product catalog management (CRUD operations)
- Inventory tracking and alerts
- Promotion and coupon creation/management
- Offer configuration (bundles, BOGO, etc.)
- Order management and fulfillment
- Customer relationship management
- Analytics dashboards
- User and permission management

**Communication**: RESTful API + WebSocket (for real-time updates)

### 2. Backend API (Laravel)
**Technology**: PHP 8.2+, Laravel 11.x

**Responsibilities**:
- Multi-tenant data isolation
- Business logic enforcement
- Authentication & authorization (Laravel Sanctum)
- RESTful API endpoints
- Database operations
- Background job processing
- File storage management
- Email and notification dispatch
- Price calculation engine
- Promotion validation
- Order workflow orchestration

**Key Modules**:
- Tenant Management
- Product & Catalog
- Inventory
- Pricing & Promotions
- Order Management
- Customer Management
- Payment Processing
- Reporting & Analytics

### 3. Storefront (Next.js)
**Technology**: Next.js 14+, TypeScript, Static Export (SSG/ISR)

**Responsibilities**:
- Product browsing and search
- Shopping cart management
- Checkout process
- Customer account area
- Order tracking
- Static page generation for SEO
- Incremental Static Regeneration for dynamic data
- Optimized performance

**Communication**: RESTful API + Next.js API routes for BFF pattern

## Data Flow

### Admin Operations Flow
```
Admin User → React SPA → API Gateway → Laravel API → Database
                                    ↓
                                  Cache/Queue
```

### Customer Shopping Flow
```
Customer → Next.js Static Pages → API (on-demand) → Laravel API → Database
                                                   ↓
                                                 Cache
```

### Order Processing Flow
```
Order Created → Queue Job → Inventory Update → Payment Processing → Notification
                         ↓
                    Promotion Validation
                         ↓
                    Price Calculation
```

## Scalability Considerations

### Horizontal Scaling
- Stateless Laravel API (multiple instances behind load balancer)
- Redis for session and cache sharing
- Database read replicas for query distribution
- CDN for static assets and storefront

### Vertical Scaling
- Database optimization (indexing, query optimization)
- Redis for hot data caching
- Queue workers for async processing
- Database connection pooling

### Future Microservices Extraction Points
1. **Product Catalog Service** - High read, independent domain
2. **Order Processing Service** - Complex workflow, isolated transactions
3. **Inventory Service** - Real-time stock management
4. **Promotion Engine** - Complex calculation logic
5. **Search Service** - Elasticsearch/Algolia integration
6. **Notification Service** - Email, SMS, Push notifications

## Multi-Tenancy Approach

**Strategy**: Single Database with Tenant Isolation

- Shared tables with `store_id` foreign key
- Global scope filtering in Laravel
- Tenant context resolution from API token
- Isolated file storage per tenant
- Configurable feature flags per store

See [07-multi-tenancy.md](07-multi-tenancy.md) for detailed implementation.

## Integration Points

### External Services
- **Payment Gateways**: Stripe, PayPal, local gateways
- **Shipping Providers**: FedEx, UPS, DHL APIs
- **Email Service**: SendGrid, Amazon SES
- **SMS Service**: Twilio, Nexmo
- **Analytics**: Google Analytics, Mixpanel
- **Search**: Algolia, Elasticsearch (optional)

### Webhooks
- Payment confirmations
- Shipping updates
- Inventory sync from external systems
- Analytics events

## Security Architecture

### Authentication
- Admin: Laravel Sanctum with SPA authentication
- API: Token-based authentication
- Storefront: Session-based + Guest checkout

### Authorization
- Role-Based Access Control (RBAC)
- Permission-based operations
- Tenant isolation enforcement
- Rate limiting per tenant

### Data Security
- Encryption at rest and in transit
- PCI DSS compliance for payment data
- GDPR compliance features
- Regular security audits

## Deployment Architecture

```
┌─────────────────────────────────────────────────┐
│              Load Balancer (Nginx)              │
└─────────────────┬───────────────────────────────┘
                  │
    ┌─────────────┼─────────────┐
    │             │             │
┌───▼───┐    ┌───▼───┐    ┌───▼───┐
│ API   │    │ API   │    │ API   │
│Server1│    │Server2│    │Server3│
└───┬───┘    └───┬───┘    └───┬───┘
    │            │            │
    └────────────┼────────────┘
                 │
    ┌────────────┼────────────┐
    │            │            │
┌───▼───┐   ┌───▼───┐   ┌───▼───┐
│Database│   │ Redis │   │ Queue │
│(Master)│   │ Cache │   │Workers│
└───┬───┘   └───────┘   └───────┘
    │
┌───▼───┐
│Database│
│(Replica│
└───────┘
```

## Technology Decisions Rationale

| Technology | Reason |
|------------|--------|
| Laravel | Mature PHP framework, excellent ORM, robust ecosystem |
| React | Component reusability, large ecosystem, mature tooling |
| Next.js | SEO optimization, static generation, excellent performance |
| Redis | Fast caching, session storage, queue driver |
| MySQL/PostgreSQL | ACID compliance, JSON support, mature replication |
| Docker | Consistent environments, easy scaling |

## Performance Targets

- API Response Time: < 200ms (95th percentile)
- Admin Panel Load: < 2s
- Storefront Load: < 1s (static pages)
- Database Queries: < 50ms average
- Concurrent Users: 10,000+ per store
- Uptime: 99.9%

## Next Steps

1. Review [Backend Architecture](02-backend-architecture.md)
2. Review [Database Schema](03-database-schema.md)
3. Review [API Design](04-api-design.md)
