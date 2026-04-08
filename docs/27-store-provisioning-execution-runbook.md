# Store Provisioning Execution Runbook

## Step 1 - Seeder Split

1. Add CoreSeeder, DemoStoreSeeder, DemoCatalogSeeder.
2. Wire DatabaseSeeder using environment flags.
3. Keep phone populated for seeded users/customers.

## Step 2 - Store Provisioning Backend

1. Add StoreProvisioningService (transactional).
2. Add StoreController endpoints:
   - POST /v1/stores
   - GET /v1/stores
   - GET /v1/stores/{id}
   - PATCH /v1/stores/{id}/status
3. Add Form Requests + policies + Scribe annotations.

## Step 3 - Cleanup Command

1. Create artisan command: PurgeMockTenantData.
2. Add options:
   - --dry-run
   - --except-demo
3. Purge only non-demo stores.

## Step 4 - Validation

- Run feature tests for auth, tenant isolation, permissions, and validation.

## Step 5 - Documentation and Progress

- Update docs/API-REFERENCE.md.
- Update .github/skills/ecommerce-api-integration/SKILL.md where relevant.
- Update PROGRESS.md.
- Regenerate Scribe docs: php artisan scribe:generate.
