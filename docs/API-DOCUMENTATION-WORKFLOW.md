# API Documentation Update Workflow

## Overview

**CRITICAL**: Whenever you create or update API endpoints, you MUST update the documentation. This ensures frontend developers always have accurate information.

---

## 📋 Workflow Checklist

### When Creating a New API Endpoint

- [ ] **1. Create Controller Method** (Backend)
  - Add method to controller with proper PHPDoc annotations
  - Include Scribe annotations (`@group`, `@queryParam`, `@response`, etc.)
  - Implement business logic via service layer

- [ ] **2. Add Route** (Backend)
  - Add route to `routes/api.php` or route group
  - Use proper HTTP method (GET, POST, PUT, DELETE)
  - Add middleware (auth, tenant scoping)

- [ ] **3. Create Form Request** (if POST/PUT/PATCH)
  - Validation rules
  - Authorization logic
  - Custom error messages

- [ ] **4. Update API Reference** (`docs/API-REFERENCE.md`)
  - Add endpoint to appropriate section
  - Document request format with example
  - Document response format with example
  - List all query parameters
  - Show error responses
  - Add to Table of Contents if new module

- [ ] **5. Update API Integration Skill** (`.github/skills/ecommerce-api-integration/SKILL.md`)
  - Add to endpoint quick reference list
  - Add TypeScript type definitions (if new resource)
  - Add RTK Query example (if common pattern)
  - Update endpoint count in resources section

- [ ] **6. Regenerate API Documentation** (Backend)
  ```bash
  php artisan scribe:generate
  ```

- [ ] **7. Test Endpoint** (Backend)
  - Write feature test
  - Test authentication
  - Test tenant isolation
  - Test validation rules
  - Run tests: `php artisan test`

- [ ] **8. Commit Changes**
  ```bash
  git add .
  git commit -m "feat: Add [endpoint name] endpoint

  - Created [ControllerName]@[method]
  - Added route: [METHOD] /api/v1/[path]
  - Updated API-REFERENCE.md
  - Updated API integration skill
  - Regenerated API docs
  - Added tests"
  ```

---

### When Updating an Existing API Endpoint

- [ ] **1. Update Controller Method** (Backend)
  - Modify method logic
  - Update PHPDoc and Scribe annotations
  - Update response structure if changed

- [ ] **2. Update Form Request** (if validation changed)
  - Add/remove/modify validation rules
  - Update error messages

- [ ] **3. Update API Reference** (`docs/API-REFERENCE.md`)
  - Modify request example if parameters changed
  - Modify response example if structure changed
  - Update query parameters list
  - Add migration notes if breaking change

- [ ] **4. Update API Integration Skill** (`.github/skills/ecommerce-api-integration/SKILL.md`)
  - Update TypeScript types if response changed
  - Update RTK Query examples if signature changed
  - Add notes about breaking changes

- [ ] **5. Regenerate API Documentation** (Backend)
  ```bash
  php artisan scribe:generate
  ```

- [ ] **6. Update Tests** (Backend)
  - Modify existing tests to match new behavior
  - Add new tests for new functionality
  - Run tests: `php artisan test`

- [ ] **7. Update CHANGELOG** (if breaking change)
  - Document breaking changes
  - Provide migration guide
  - Add version number

- [ ] **8. Commit Changes**
  ```bash
  git add .
  git commit -m "fix: Update [endpoint name] endpoint

  - Modified [ControllerName]@[method]
  - Changed [what changed]
  - Updated API-REFERENCE.md
  - Updated API integration skill
  - Regenerated API docs
  - Updated tests
  
  BREAKING CHANGE: [if applicable]"
  ```

---

### When Deleting an API Endpoint

- [ ] **1. Remove Route** (Backend)
  - Comment out or remove route from `routes/api.php`

- [ ] **2. Deprecate Controller Method** (Backend - if still needed)
  - Add `@deprecated` annotation
  - Add deprecation notice in response
  - OR remove method entirely if not needed

- [ ] **3. Remove from API Reference** (`docs/API-REFERENCE.md`)
  - Delete endpoint documentation
  - OR move to "Deprecated Endpoints" section
  - Update Table of Contents
  - Update endpoint count

- [ ] **4. Remove from API Integration Skill** (`.github/skills/ecommerce-api-integration/SKILL.md`)
  - Remove from endpoint quick reference
  - Remove related examples
  - Update endpoint count

- [ ] **5. Regenerate API Documentation** (Backend)
  ```bash
  php artisan scribe:generate
  ```

- [ ] **6. Update CHANGELOG**
  - Document deprecated/removed endpoint
  - Provide alternative endpoint if available

- [ ] **7. Commit Changes**
  ```bash
  git add .
  git commit -m "refactor: Remove [endpoint name] endpoint

  - Removed route: [METHOD] /api/v1/[path]
  - Updated API-REFERENCE.md
  - Updated API integration skill
  - Regenerated API docs
  
  BREAKING CHANGE: Endpoint removed, use [alternative] instead"
  ```

---

## 📁 Files to Update

| File | Purpose | When to Update |
|------|---------|----------------|
| **Controller** | Backend logic | Always |
| **routes/api.php** | Route definition | Always |
| **Form Request** | Validation | When request changes |
| **docs/API-REFERENCE.md** | Complete API docs | Always |
| **.github/skills/ecommerce-api-integration/SKILL.md** | Copilot skill | Always |
| **Scribe docs** | Interactive docs | Always (regenerate) |
| **Tests** | Feature tests | Always |
| **CHANGELOG.md** | Breaking changes | When breaking |

---

## 🎯 Documentation Standards

### API-REFERENCE.md Format

```markdown
### [Endpoint Name]
```http
[METHOD] /[path]?[query_params]
Authorization: Bearer {token}
X-Store-ID: {store_id}
Content-Type: application/json

{
  "field": "value"
}
```

**Query Parameters**:
- `param_name` (type): Description

**Response 200**:
```json
{
  "data": { ... }
}
```

**Response 422 (Validation Error)**:
```json
{
  "message": "...",
  "errors": { ... }
}
```
```

### API Integration Skill Format

```markdown
## [Module Name] ([count] endpoints)
- `[METHOD] /[path]` - Description
- `[METHOD] /[path]` - Description
```

With RTK Query example for common patterns:
```markdown
### [Pattern Name]
```typescript
// Endpoint definition
[methodName]: builder.[query|mutation]<[ResponseType], [ParamsType]>({
  query: (params) => ({
    url: '/[path]',
    method: '[METHOD]',
    body: params,
  }),
});

// Usage
const { data } = use[MethodName][Query|Mutation](params);
```
```

---

## 🔍 Pre-Commit Checklist

Before committing API changes, verify:

```bash
# 1. Backend tests pass
cd platform/backend
php artisan test

# 2. API docs regenerated
php artisan scribe:generate

# 3. Check API-REFERENCE.md updated
git diff docs/API-REFERENCE.md

# 4. Check API skill updated
git diff .github/skills/ecommerce-api-integration/SKILL.md

# 5. Stage all changes
git add .

# 6. Commit with descriptive message
git commit -m "feat: [description]

- Updated API-REFERENCE.md
- Updated API integration skill
- Regenerated Scribe docs
- Added/updated tests"
```

---

## 🚨 Common Mistakes to Avoid

❌ **Creating endpoint without updating docs**
- Frontend developers won't know it exists
- Leads to inconsistent usage

❌ **Forgetting to regenerate Scribe docs**
- Interactive docs become outdated
- Postman collection missing new endpoints

❌ **Not updating TypeScript types in skill**
- Frontend gets type errors
- Copilot suggests wrong types

❌ **Breaking changes without CHANGELOG**
- Frontend breaks unexpectedly
- No migration guide available

❌ **Updating one doc file but not the other**
- Inconsistent information
- Confusion about correct usage

❌ **Not testing tenant isolation**
- Security vulnerability
- Data leakage between stores

---

## 📊 Documentation Sync Verification

Run this checklist monthly to ensure docs are in sync:

```bash
# Count endpoints in backend routes
cd platform/backend
grep -r "Route::" routes/api.php | wc -l

# Check endpoint count in API-REFERENCE.md
grep "Total Endpoints:" docs/API-REFERENCE.md

# Check endpoint count in API skill
grep "endpoint" .github/skills/ecommerce-api-integration/SKILL.md

# All counts should match!
```

---

## 🎓 Training for New Developers

When onboarding new developers:

1. **Show them this workflow document**
2. **Walk through one complete example** (create endpoint → update docs → commit)
3. **Have them practice** with a simple endpoint
4. **Code review checklist** - Verify docs updated
5. **Set up pre-commit hook** (optional) to remind about docs

---

## 🔧 Automation Ideas (Future)

Consider adding:

- Pre-commit hook that checks if API-REFERENCE.md changed when controller changed
- CI/CD check that fails if Scribe docs not regenerated
- Script to auto-generate basic docs from controller annotations
- Bot that comments on PRs: "Did you update API-REFERENCE.md?"

---

## 📚 Related Documents

- [docs/API-REFERENCE.md](API-REFERENCE.md) - Complete API reference
- [.github/skills/ecommerce-api-integration/SKILL.md](../.github/skills/ecommerce-api-integration/SKILL.md) - Copilot skill
- [docs/16-api-documentation-system.md](16-api-documentation-system.md) - Scribe setup
- [docs/04-api-design.md](04-api-design.md) - API design patterns

---

**Last Updated**: April 7, 2026  
**Version**: 1.0
