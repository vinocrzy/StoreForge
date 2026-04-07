# Test Login

Testing authentication with seeded credentials.

## Request
```http
POST http://localhost:8000/api/v1/auth/login
Content-Type: application/json

{
  "login": "admin@ecommerce-platform.com",
  "password": "password"
}
```

## Expected Response (200 OK)
```json
{
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@ecommerce-platform.com",
    "phone": null,
    "status": "active"
  },
  "token": "1|...",
  "stores": [
    {
      "id": 1,
      "name": "Demo Fashion Store",
      "slug": "demo-fashion",
      "role": "owner"
    },
    {
      "id": 2,
      "name": "Demo Electronics Store",
      "slug": "demo-electronics",
      "role": "owner"
    },
    {
      "id": 3,
      "name": "Demo Home Decor Store",
      "slug": "demo-homedecor",
      "role": "owner"
    }
  ]
}
```

## Using curl
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "admin@ecommerce-platform.com",
    "password": "password"
  }'
```

## Using PowerShell
```powershell
$body = @{
    login = "admin@ecommerce-platform.com"
    password = "password"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/v1/auth/login" `
  -Method Post `
  -ContentType "application/json" `
  -Body $body
```
