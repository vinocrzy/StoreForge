# Lessons Learned: Admin Panel Development (April 10, 2026)

## Session Overview

**Duration**: ~3 hours  
**Context**: Phase 8.2 Storefront API Integration + Admin Panel Bug Fixes  
**Critical Issues Fixed**: 5 major bugs, 8 edge cases documented

---

## 🔴 Critical Issues Encountered

### 1. Authentication Logout Loop (CRITICAL)

**Symptom**: User logs in successfully → Redirected to dashboard → Immediately logged out → Redirected to /signin

**Root Cause**: Backend login response returned incomplete store objects:
```json
{
  "stores": [
    { "id": 1, "name": "Store", "slug": "store" }
    // Missing: domain, status, currency, created_at, updated_at
  ]
}
```

Frontend expected:
```typescript
interface Store {
  id: number;
  name: string;
  domain: string;      // ❌ Missing
  status: string;      // ❌ Missing
  currency: string;    // ❌ Missing
  created_at: string;  // ❌ Missing
  updated_at: string;  // ❌ Missing
}
```

**Fix**: Updated `AuthController.php` to return complete store objects with all fields including defaults:
```php
return [
    'id' => $store->id,
    'name' => $store->name,
    'slug' => $store->slug,
    'domain' => $store->domain,
    'status' => $store->status,
    'currency' => $store->currency ?? 'USD',  // With fallback
    'created_at' => $store->created_at->toISOString(),
    'updated_at' => $store->updated_at->toISOString(),
];
```

**Prevention**: Always compare backend API responses to frontend TypeScript interfaces BEFORE implementing mutations.

---

### 2. Image Upload: Content-Type Header Conflict (CRITICAL)

**Symptom**: No API request triggered when uploading images in admin panel

**
Root Cause**: RTK Query `prepareHeaders` was setting `Content-Type: application/json` globally, which prevented FormData uploads from working.

```tsx
// ❌ WRONG - Breaks multipart uploads
prepareHeaders: (headers) => {
  headers.set('Content-Type', 'application/json'); // ❌ Breaks FormData!
  return headers;
}
```

**Why This Breaks**:
- Browser MUST set `Content-Type: multipart/form-data; boundary=----WebKitFormBoundary...` automatically
- Manual `Content-Type` header overrides browser's boundary parameter
- Backend can't parse request without correct boundary

**Fix**: Removed `Content-Type` from `prepareHeaders` - let fetchBaseQuery handle it automatically:
```tsx
// ✅ CORRECT
prepareHeaders: (headers) => {
  headers.set('Accept', 'application/json');
  // Let fetchBaseQuery auto-set Content-Type:
  // - Plain objects → application/json
  // - FormData → multipart/form-data (with boundary)
  return headers;
}
```

**Prevention**: NEVER manually set `Content-Type` for FormData in RTK Query mutations.

---

### 3. Image Upload: Database Field Mismatch (CRITICAL)

**Symptom**: `SQLSTATE[HY000]: NOT NULL constraint failed: product_images.file_path`

**Root Cause**: Code used `url` field but database has `file_path` column:

```php
// ❌ WRONG - Using non-existent column
ProductImage::create([
    'url' => '/storage/image.jpg',  // ❌ Column doesn't exist!
]);

// Migration actually has:
$table->string('file_path'); // ✅ Actual column name
```

**Fix**: 
1. Changed create() to use correct column:
```php
ProductImage::create([
    'file_path' => 'products/1/image.jpg',  // ✅ Matches DB
]);
```

2. Added accessor for API responses:
```php
protected $appends = ['url'];

public function getUrlAttribute(): string {
    return url('storage/' . $this->file_path);
}
```

**Prevention**: Always verify database schema BEFORE writing model code. Run `php artisan migrate` and check actual table structure.

---

### 4. Image Upload: Collection Type Mismatch

**Symptom**: `Return value must be of type Illuminate\Database\Eloquent\Collection, Illuminate\Support\Collection returned`

**Root Cause**: Used `collect()` which returns `Support\Collection`, but function signature required `Eloquent\Collection`:

```php
// ❌ WRONG
public function uploadProductImages(...): Collection
{
    $uploadedImages = collect(); // Returns Support\Collection
    return $uploadedImages; // ❌ Type mismatch!
}
```

**Fix**:
```php
// ✅ CORRECT
use Illuminate\Database\Eloquent\Collection;

public function uploadProductImages(...): Collection
{
    $uploadedImages = new Collection(); // Eloquent\Collection
    $uploadedImages->push($model); // Push Eloquent models
    return $uploadedImages; // ✅ Type matches
}
```

**Prevention**: 
- Use `new Collection()` for Eloquent models
- Use `collect()` for general array manipulation
- Always check function return type declarations

---

### 5. Image Upload: Syntax Error from Missing Line Break

**Symptom**: `Undefined variable $setPrimary`

**Root Cause**: Variable assignment on same line as comment:
```php
// Determine if we should set the first uploaded image as primary        $setPrimary = $options['is_primary'] ?? true;
//                                                                        ↑ Treated as part of comment!
```

**Fix**: Add line break after comment:
```php
// Determine if we should set the first uploaded image as primary
$setPrimary = $options['is_primary'] ?? true; // ✅
```

**Prevention**: IDE auto-formatting should catch this. Always use PSR-12 code style.

---

## 📚 Skills Updated

### 1. Created: `ecommerce-backend-patterns/SKILL.md`

**New sections**:
- File upload handling patterns
- Collection type distinctions (Eloquent vs Support)
- API response completeness validation
- Database schema vs model field mapping
- Common error messages with fixes
- Pre-commit checklist for backend

### 2. Updated: `ecommerce-admin-ui/SKILL.md`

**New sections**:
- CRITICAL: File Upload with RTK Query (complete pattern)
- Content-Type header conflict resolution
- FormData best practices
- Edit vs New page upload patterns
- Backend response validation
- File upload error reference table

---

## 🎯 Development Best Practices Established

### Before Writing Any Code

1. **Frontend**:
   - [ ] Check if backend endpoint exists and matches expected signature
   - [ ] Compare TypeScript interface to actual API response
   - [ ] Test backend in Scribe/Postman first
   - [ ] Verify FormData uploads don't set Content-Type manually

2. **Backend**:
   - [ ] Check database schema matches model `$fillable`
   - [ ] Verify frontend TypeScript interface expectations
   - [ ] Use correct Collection type (Eloquent vs Support)
   - [ ] Test file uploads with `curl` before frontend integration
   - [ ] Run migrations if created

### Before Committing

**Frontend Checklist**:
- [ ] `npm run build` passes (0 TypeScript errors)
- [ ] No Content-Type in FormData mutations
- [ ] All API responses validated against interfaces
- [ ] Loading states for async operations

**Backend Checklist**:
- [ ] `php artisan migrate` run if migrations created
- [ ] `php artisan scribe:generate` if API changed
- [ ] Database columns match model fields
- [ ] API responses include ALL required fields
- [ ] File paths stored as relative, not absolute
- [ ] Test with actual file uploads/API calls

---

## 🔍 Edge Cases Documented

1. **Edit Page Image Uploads**: Only upload NEW images (filter by `img.file` property)
2. **Global vs Multipart Content-Type**: Never set Content-Type in `prepareHeaders`
3. **Database Field Accessors**: Use `protected $appends` to include computed fields in JSON
4. **Primary Image Logic**: Check existing primary before setting new one
5. **File Deletion**: Use `file_path` directly with `Storage::delete()`
6. **Login Response Completeness**: Include currency with fallback: `?? 'USD'`
7. **Multi-Step Mutations**: Create product → Upload images (two separate API calls)
8. **Loading State Composition**: Track both `isLoading` AND `isUploadingImages`

---

## 📊 Impact Assessment

**Time Saved for Future Development**:
- FormData upload issues: ~2 hours (well-documented pattern)
- Backend response mismatches: ~1 hour (validation checklist)
- Collection type errors: ~30 mins (clear distinction documented)
- Database field mapping: ~1 hour (pre-check workflow)

**Total Estimated Time Savings**: ~4.5 hours per similar feature

**Code Quality Improvements**:
- Type safety: All potential mismatches now pre-checked
- Error handling: Comprehensive patterns for file uploads
- Documentation: Frontend-backend integration fully mapped

---

## 🚀 Key Takeaways

1. **RTK Query + FormData**: Browser must set Content-Type automatically
2. **Backend Responses**: Must match frontend TypeScript interfaces EXACTLY
3. **Database Schema**: Always verify BEFORE writing model code
4. **Collection Types**: Eloquent for models, Support for general arrays
5. **Multi-Step Operations**: Track loading states for ALL async steps
6. **Testing First**: Backend endpoint → Postman → Frontend integration

---

## 📖 Related Documentation

- [Admin Panel Design System](../docs/19-admin-panel-design-system.md)
- [API Documentation Workflow](../docs/API-DOCUMENTATION-WORKFLOW.md)
- [Test Accounts](../docs/TEST-ACCOUNTS.md)

---

**Session Date**: April 10, 2026  
**Skills Updated**: 2 (1 created, 1 enhanced)  
**Critical Bugs Fixed**: 5  
**Edge Cases Documented**: 8  
**Estimated Future Time Savings**: 4.5 hours/feature
