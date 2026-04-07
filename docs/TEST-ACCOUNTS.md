# Test Accounts for Development

This document lists all test accounts created by the database seeders for development and testing.

---

## 🔐 Admin Panel Access

### Base URL
- **Local**: http://localhost:5173
- **Login Page**: http://localhost:5173/login

### Authentication
- **Method**: Phone or Email + Password
- **All Passwords**: `password`

---

## 👨‍💼 Super Admin (Platform Access)

**Full access to all stores and platform settings**

| Field | Value |
|-------|-------|
| **Email** | `admin@ecommerce-platform.com` |
| **Phone** | Not set (use email) |
| **Password** | `password` |
| **Role** | Super Admin |
| **Access** | All stores (owner role) |

**Use this account to**:
- Access all stores from one login
- Manage platform-wide settings
- Test cross-store functionality
- Perform administrative tasks

---

## 🏪 Store-Specific Accounts

Each demo store has 4 user accounts with different permission levels.

### Store 1: Demo Fashion Store

**Store Details**:
- **Name**: Demo Fashion Store
- **Slug**: `demo-fashion`
- **Domain**: fashion.demo.localhost
- **Store ID**: 1

| Role | Email | Phone | Access Level |
|------|-------|-------|--------------|
| **Owner** | `owner@demo-fashion.com` | Not set | Full store access + settings |
| **Admin** | `admin@demo-fashion.com` | Not set | Manage products, orders, customers |
| **Manager** | `manager@demo-fashion.com` | Not set | View & manage orders, inventory |
| **Staff** | `staff@demo-fashion.com` | Not set | Basic order processing |

**Password**: `password` for all accounts

---

### Store 2: Demo Electronics Store

**Store Details**:
- **Name**: Demo Electronics Store
- **Slug**: `demo-electronics`
- **Domain**: electronics.demo.localhost
- **Store ID**: 2

| Role | Email | Phone | Access Level |
|------|-------|-------|--------------|
| **Owner** | `owner@demo-electronics.com` | Not set | Full store access + settings |
| **Admin** | `admin@demo-electronics.com` | Not set | Manage products, orders, customers |
| **Manager** | `manager@demo-electronics.com` | Not set | View & manage orders, inventory |
| **Staff** | `staff@demo-electronics.com` | Not set | Basic order processing |

**Password**: `password` for all accounts

---

### Store 3: Demo Home Decor Store

**Store Details**:
- **Name**: Demo Home Decor Store
- **Slug**: `demo-homedecor`
- **Domain**: homedecor.demo.localhost
- **Store ID**: 3

| Role | Email | Phone | Access Level |
|------|-------|-------|--------------|
| **Owner** | `owner@demo-homedecor.com` | Not set | Full store access + settings |
| **Admin** | `admin@demo-homedecor.com` | Not set | Manage products, orders, customers |
| **Manager** | `manager@demo-homedecor.com` | Not set | View & manage orders, inventory |
| **Staff** | `staff@demo-homedecor.com` | Not set | Basic order processing |

**Password**: `password` for all accounts

---

## 👥 Customer Accounts (Storefront)

**45 customer accounts created** (15 per store)

### Pattern
- **Emails**: `customer1@example.com` to `customer45@example.com`
- **Phones**: `+15551000000` to `+15551000044` (E.164 format)
- **Password**: `password` for all accounts
- **Status**: ~75% active, ~25% inactive

### Distribution
- **Store 1 (Fashion)**: customer1@example.com to customer15@example.com
- **Store 2 (Electronics)**: customer16@example.com to customer30@example.com
- **Store 3 (Home Decor)**: customer31@example.com to customer45@example.com

### Example Customer Accounts
```
Store 1 (Fashion):
  customer1@example.com / password
  customer2@example.com / password
  ...

Store 2 (Electronics):
  customer16@example.com / password
  customer17@example.com / password
  ...

Store 3 (Home Decor):
  customer31@example.com / password
  customer32@example.com / password
  ...
```

### Customer Features
- Each customer has 1-3 addresses
- ~80% have verified emails
- 100% have verified phones
- ~60% have recent login activity
- Random demographics (name, DOB, gender)

---

## 🔑 Quick Access Guide

### For Admin Panel Testing

**Best Account to Start**:
```
Email: admin@ecommerce-platform.com
Password: password
```
This gives you access to all stores.

**For Single Store Testing**:
```
Email: owner@demo-fashion.com
Password: password
Store ID: 1
```

**For Testing Permissions**:
- Use `owner@...` for full access
- Use `admin@...` for content management
- Use `manager@...` for operational tasks
- Use `staff@...` for limited access

### For Storefront Testing (Future)

**Customer Login**:
```
Email: customer1@example.com
Password: password
Store: Demo Fashion Store
```

### For API Testing

**Request Headers**:
```http
POST http://localhost:8000/api/v1/auth/login
Content-Type: application/json

{
  "login": "admin@ecommerce-platform.com",
  "password": "password"
}
```

**Response** (save the token):
```json
{
  "token": "1|xyz123...",
  "user": { ... },
  "stores": [ ... ]
}
```

**Authenticated Requests**:
```http
GET http://localhost:8000/api/v1/products
Authorization: Bearer {token}
X-Store-ID: 1
```

---

## 📊 Test Data Summary

| Resource | Count | Notes |
|----------|-------|-------|
| **Stores** | 3 | Fashion, Electronics, Home Decor |
| **Admin Users** | 13 | 1 super admin + 4 per store |
| **Customers** | 45 | 15 per store |
| **Addresses** | ~90 | 1-3 per customer |
| **Categories** | 84 | 28 per store |
| **Products** | 90 | 30 per store |
| **Product Images** | 228 | 2-3 per product |
| **Warehouses** | 3 | 1 per store |
| **Inventory Records** | 90 | 1 per product |

---

## 🔄 Resetting Test Data

### To reset all data and reseed:

```bash
cd platform/backend

# Reset database
php artisan migrate:fresh --seed

# This will recreate all test accounts
```

### To create additional test users:

```bash
# Run specific seeder
php artisan db:seed --class=UserSeeder

# Or run all seeders
php artisan db:seed
```

---

## 🛡️ Security Notes

⚠️ **IMPORTANT - Development Only**:

- All accounts use `password` as the password
- These are **NOT secure** for production
- Phone numbers are fake (+1555...)
- Email addresses use example.com
- Never use these accounts in production
- Change all passwords before going live

### Production Setup
Before deploying to production:

1. ✅ Delete all seeded test accounts
2. ✅ Create real admin accounts with strong passwords
3. ✅ Use real phone numbers (E.164 format)
4. ✅ Use real email addresses
5. ✅ Enable 2FA for admin accounts
6. ✅ Set up proper role-based access control
7. ✅ Review and adjust permissions

---

## 🧪 Testing Scenarios

### Test Multi-Tenancy
1. Login as `admin@ecommerce-platform.com`
2. Switch between stores in the UI
3. Verify data isolation (products from Store 1 don't show in Store 2)

### Test Permissions
1. Login as different roles (owner, admin, manager, staff)
2. Try to access restricted resources
3. Verify permission checks work

### Test Phone Authentication
1. Get phone number from customer seeder output
2. Login with phone instead of email
3. Example: `+15551000000` / `password`

### Test Customer Flow
1. Login as customer (customer1@example.com)
2. View products
3. Place order
4. Check order history
5. Manage addresses

---

## 📝 Common Login Issues

### "The email field is required"
- ✅ **Fixed**: Backend now accepts `login` field
- Use either phone or email in the same field

### "Unauthenticated"
- Check if token is valid
- Verify `Authorization: Bearer {token}` header is set
- Token may have expired (logout and login again)

### "Store not found"
- Verify `X-Store-ID` header is set correctly
- Check if user has access to this store
- Store IDs: 1 (Fashion), 2 (Electronics), 3 (Home Decor)

### Can't login to storefront
- Storefront not yet implemented (Phase 4)
- Currently only admin panel is functional

---

## 🔗 Related Documentation

- [API Reference](API-REFERENCE.md) - Complete API documentation
- [API Documentation Workflow](API-DOCUMENTATION-WORKFLOW.md) - How to update docs
- [Getting Started Guide](11-getting-started.md) - Setup instructions
- [Multi-Tenancy](07-multi-tenancy.md) - Tenant isolation details

---

**Last Updated**: April 7, 2026  
**Environment**: Development  
**Admin Panel**: http://localhost:5173  
**Backend API**: http://localhost:8000
