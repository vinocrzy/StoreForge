# Store Provisioning, Mock Data Cleanup, and Production Gap Plan

## Goals

1. Remove placeholder/mock data from client-honey-bee.
2. Keep exactly one demo store.
3. Start every new storefront with clean tenant data.
4. Keep Super Admin credentials active.
5. Provide admin workflow to create and view stores.
6. Close production gaps with clear deliverables.

## Target State

- storefront-template has no hardcoded client/demo content.
- Tenant isolation enforced by store_id scope + middleware.
- Seed split:
  - CoreSeeder (system-only)
  - DemoStoreSeeder (single demo store)
  - DemoCatalogSeeder (optional, non-prod only)
- Admin panel has Stores module (list/create/detail/status).
- New store provisioning creates clean baseline only.

## Seeding Policy

- Keep:
  - Super Admin
  - Roles/permissions
  - One demo store
- Remove:
  - Seeded mock customers/products/orders from non-demo stores
  - Placeholder content in client-honey-bee

### Environment Flags

- SEED_SUPER_ADMIN=true
- SEED_DEMO_STORE=true
- SEED_DEMO_STORE_USERS=true
- SEED_DEMO_DATA=false
- SEED_MOCK_DATA=false

## Admin Workflow

### Create Store (Super Admin)

Admin > Stores > Create

Required fields:
- Store name
- Store slug (unique)
- Owner name
- Owner phone (required, E.164)
- Owner email (optional)
- Currency
- Timezone
- Status

Behavior:
- Transaction: create store + owner + role + defaults
- Audit log entry
- Dispatch StoreProvisioned event (optional)

### View Stores

Admin > Stores

Columns:
- ID, Name, Slug, Domain, Status, Created At, Owner

Filters:
- status, date range, search by name/slug/phone

## Cleanup Plan

### Phase A - Backup and Audit

1. Full DB backup
2. Export current store list
3. Mark a single demo store by slug (demo-store)

### Phase B - Remove Placeholder Content

1. Delete client-honey-bee placeholder branding/assets/content
2. Use neutral values from storefront-template
3. Remove fake env/demo keys

### Phase C - Purge Mock Tenant Data

1. Keep Super Admin + demo store
2. Delete tenant data by store_id for all non-demo stores
3. Verify zero cross-tenant residual data

### Phase D - Seeder Refactor

1. Create CoreSeeder, DemoStoreSeeder, DemoCatalogSeeder
2. Guard by env flags
3. Update local/staging/prod seed pipelines

## Backend/API Gaps

- POST /v1/stores (Super Admin only)
- GET /v1/stores (Super Admin only)
- GET /v1/stores/{id} (Super Admin only)
- PATCH /v1/stores/{id}/status
- Store provisioning service with transaction + audit

## Storefront Template Gaps

- Remove hardcoded demo/client content
- Load branding/config from store config API
- Add empty states (No products yet, Setup required)
- Keep SEO defaults valid for empty catalog

## Security and Isolation

- Do not trust client-supplied store_id
- Resolve tenant from auth + middleware
- Add tenant isolation tests for all new endpoints

## Production Readiness Checklist

- [ ] Stores admin UI complete
- [ ] Store APIs + service + tests complete
- [ ] Seeder split complete
- [ ] Mock purge command with dry-run mode
- [ ] Audit logs enabled
- [ ] API docs updated (Scribe + docs/API-REFERENCE.md)
- [ ] PROGRESS.md updated

## Acceptance Criteria

1. Super Admin can create stores from admin panel.
2. New stores are clean (no catalog/orders/customers).
3. Only one demo store exists and is linkable to storefront-template.
4. Super Admin credentials remain active.
5. client-honey-bee has no placeholder/mock content.
6. Tenant isolation and permission tests pass.
