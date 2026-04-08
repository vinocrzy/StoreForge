# Client Storefront Creation Guide

## Overview

This guide walks you through creating a new white-label storefront for a client customer using the storefront template.

---

## Prerequisites

Before creating a new client storefront, ensure:

1. ✅ **Backend is running** - The API must be accessible (localhost:8000 or production)
2. ✅ **Store record exists** - Create a new store in the database with a unique `store_id`
3. ✅ **Template is ready** - The `storefront-template/` directory exists and is up-to-date
4. ✅ **Node.js installed** - Version 18+ required for Next.js 14

---

## Quick Start (Automated Method)

### 1. Create Store in Database First

Before creating the storefront, you need a store record in the database:

```sql
-- Connect to your database
USE ecommerce_platform;

-- Create new store
INSERT INTO stores (
    name,
    slug,
    domain,
    email,
    phone,
    is_active,
    created_at,
    updated_at
) VALUES (
    'Fashion Boutique',           -- Store name
    'fashion-boutique',           -- URL slug
    'fashion.example.com',        -- Domain (optional)
    'contact@fashion.example.com',-- Support email
    '+1234567890',                -- Support phone
    1,                            -- Active (1) or inactive (0)
    NOW(),
    NOW()
);

-- Get the store ID (you'll need this)
SELECT id, name, slug FROM stores ORDER BY id DESC LIMIT 1;
-- Example result: id=2, name='Fashion Boutique', slug='fashion-boutique'
```

**Via API** (preferred):
```bash
# Using admin panel or API directly
POST http://localhost:8000/api/v1/admin/stores
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "Fashion Boutique",
  "slug": "fashion-boutique",
  "email": "contact@fashion.example.com",
  "phone": "+1234567890",
  "domain": "fashion.example.com",
  "is_active": true
}

# Response includes store_id
```

### 2. Run the Creation Script

```powershell
# Navigate to project root
cd c:\poc\e-com

# Run the script with client name and store ID
.\scripts\create-client-store.ps1 "Fashion Boutique" 2

# Examples:
.\scripts\create-client-store.ps1 "Tech Electronics" 3
.\scripts\create-client-store.ps1 "Organic Foods Store" 4
.\scripts\create-client-store.ps1 "Luxury Watches" 5
```

**Script will**:
- ✅ Copy storefront template → `client-fashion-boutique/`
- ✅ Remove template git history
- ✅ Initialize new git repository
- ✅ Create `.env.local` with store ID and name
- ✅ Update `package.json` with client name
- ✅ Create initial commit
- ✅ Create `development` and `staging` branches

### 3. Navigate to Storefront

```powershell
cd client-fashion-boutique

# Install dependencies
npm install

# Start development server
npm run dev
```

Open http://localhost:3000 to view the storefront.

---

## Manual Method (Step-by-Step)

If you prefer manual setup or the script doesn't work:

### Step 1: Clone Template

```powershell
cd c:\poc\e-com

# Copy the template
Copy-Item -Path "storefront-template" -Destination "client-fashion-boutique" -Recurse
cd client-fashion-boutique
```

### Step 2: Initialize Git

```powershell
# Remove template git history
Remove-Item -Path ".git" -Recurse -Force

# Initialize new repository
git init
git add .
git commit -m "chore: initialize Fashion Boutique storefront"
```

### Step 3: Configure Store

Create `.env.local` file:

```env
# Store Configuration
NEXT_PUBLIC_STORE_ID=2
NEXT_PUBLIC_STORE_NAME=Fashion Boutique
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1

# Production (update before deployment)
# NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1
# NEXT_PUBLIC_STRIPE_KEY=pk_live_xxxxx
```

### Step 4: Update Package Name

Edit `package.json`:

```json
{
  "name": "client-fashion-boutique",
  "description": "Fashion Boutique storefront",
  "version": "1.0.0",
  ...
}
```

### Step 5: Install & Run

```powershell
npm install
npm run dev
```

---

## Customization

### 1. Theme Configuration

Edit `src/config/theme.ts`:

```typescript
export const themeConfig: ThemeConfig = {
  storeName: 'Fashion Boutique',
  
  colors: {
    primary: '#D946EF',        // Brand purple
    secondary: '#9333EA',      // Darker purple
    accent: '#F0ABFC',         // Light purple
    background: '#FFFFFF',
    text: '#1F2937',
  },
  
  fonts: {
    heading: 'Playfair Display',  // Elegant serif
    body: 'Inter',                // Clean sans-serif
  },
  
  logo: {
    src: '/images/fashion-logo.png',
    alt: 'Fashion Boutique Logo',
    width: 200,
    height: 60,
  },
  
  // ... more customization
};
```

### 2. Branding Assets

```powershell
# Add client logo
# Place logo images in public/images/
client-fashion-boutique/
  public/
    favicon.ico          # ← Replace with client favicon
    images/
      logo.png           # ← Client logo (light background)
      logo-dark.png      # ← Client logo (dark background)
      hero-banner.jpg    # ← Homepage hero image
```

### 3. Homepage Customization

Edit `src/app/page.tsx`:

```typescript
export default function HomePage() {
  return (
    <main>
      {/* Custom hero section */}
      <HeroSection 
        title="Discover Premium Fashion"
        subtitle="Curated collections for the modern wardrobe"
        ctaText="Shop Now"
        backgroundImage="/images/hero-banner.jpg"
      />
      
      {/* Featured products */}
      <FeaturedProducts storeId={2} />
      
      {/* Client-specific sections */}
      <CustomSection1 />
      <CustomSection2 />
    </main>
  );
}
```

### 4. SEO & Metadata

Edit `src/app/layout.tsx`:

```typescript
export const metadata: Metadata = {
  title: {
    default: 'Fashion Boutique - Premium Clothing & Accessories',
    template: '%s | Fashion Boutique'
  },
  description: 'Discover our curated collection of premium fashion, from elegant dresses to luxury accessories. Free shipping on orders over $100.',
  keywords: ['fashion', 'premium clothing', 'luxury accessories', 'online boutique'],
  openGraph: {
    type: 'website',
    siteName: 'Fashion Boutique',
    images: ['/images/og-image.jpg'],
  },
};
```

---

## Testing Checklist

Before launching the client storefront, verify:

### Development Testing
- [ ] Storefront loads at http://localhost:3000
- [ ] Products display correctly (fetching from backend via store_id)
- [ ] Categories navigation works
- [ ] Product detail pages load
- [ ] Add to cart functionality
- [ ] Checkout flow (test mode)
- [ ] Customer account pages
- [ ] Search functionality
- [ ] Mobile responsive design
- [ ] Theme colors and branding correct

### API Integration
- [ ] Store ID correctly set in `.env.local`
- [ ] API calls include store ID in requests
- [ ] Products are filtered by store_id
- [ ] Orders are created with correct store_id
- [ ] Multi-tenant isolation verified (only this store's data shown)

### Performance
- [ ] Images optimized (WebP format)
- [ ] Lighthouse score > 90
- [ ] First Contentful Paint < 1.5s
- [ ] Static pages pre-rendered (SSG)

### SEO
- [ ] Meta titles and descriptions customized
- [ ] Open Graph tags configured
- [ ] Sitemap.xml generated (`/sitemap.xml`)
- [ ] Robots.txt configured (`/robots.txt`)
- [ ] Schema.org Product markup present

---

## Deployment

### Option 1: Vercel (Recommended for Next.js)

```powershell
# Install Vercel CLI
npm install -g vercel

# Navigate to storefront
cd client-fashion-boutique

# Login to Vercel
vercel login

# Deploy
vercel

# Follow prompts:
# - Project name: fashion-boutique
# - Which directory: ./
# - Build command: npm run build
# - Output directory: .next
# - Development command: npm run dev

# Add environment variables in Vercel dashboard:
# - NEXT_PUBLIC_STORE_ID=2
# - NEXT_PUBLIC_STORE_NAME=Fashion Boutique
# - NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1
# - NEXT_PUBLIC_STRIPE_KEY=pk_live_xxxxx

# Deploy to production
vercel --prod
```

### Option 2: Custom Server (Ubuntu/Nginx)

```bash
# On server
cd /var/www
git clone <client-repo-url> fashion-boutique
cd fashion-boutique

# Install dependencies
npm install

# Create .env.local (production)
cat > .env.local << EOF
NEXT_PUBLIC_STORE_ID=2
NEXT_PUBLIC_STORE_NAME=Fashion Boutique
NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1
NEXT_PUBLIC_STRIPE_KEY=pk_live_xxxxx
EOF

# Build
npm run build

# Start with PM2
pm2 start npm --name "fashion-boutique" -- start
pm2 save
```

**Nginx configuration**:

```nginx
server {
    listen 80;
    server_name fashion.example.com;
    
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### Option 3: Static Export (S3/CloudFront)

For fully static sites (no server-side features):

```powershell
# Edit next.config.ts
export default {
  output: 'export',
  images: {
    unoptimized: true,
  },
};

# Build static export
npm run build

# Deploy 'out' directory to S3, Netlify, or CloudFront
```

---

## Git Workflow

### Create Remote Repository

```powershell
cd client-fashion-boutique

# Create repo on GitHub/GitLab/Bitbucket first, then:
git remote add origin https://github.com/client-org/fashion-boutique.git
git push -u origin main
git push origin development staging
```

### Development Workflow

```powershell
# Feature development
git checkout development
git checkout -b feature/custom-homepage
# ... make changes ...
git add .
git commit -m "feat: add custom homepage design"
git push origin feature/custom-homepage

# Merge to development
git checkout development
git merge feature/custom-homepage
git push origin development

# Deploy to staging
git checkout staging
git merge development
git push origin staging
# Trigger staging deployment

# After client approval, deploy to production
git checkout main
git merge staging
git push origin main
# Trigger production deployment
```

---

## Multi-Store Best Practices

### 1. **Keep Template Updated**

When you improve the base template:

```powershell
cd storefront-template

# Make improvements
git commit -m "feat: improve product card component"
git tag v1.1.0
```

**Sync to existing clients**:

```powershell
cd client-fashion-boutique

# Add template as remote (one-time)
git remote add template ../storefront-template

# Fetch updates
git fetch template

# Cherry-pick specific improvements
git cherry-pick <commit-hash>

# Or merge all changes
git merge template/main
```

### 2. **Environment Variables**

Never commit `.env.local` - keep it in `.gitignore`.

**Required variables**:
```env
NEXT_PUBLIC_STORE_ID=2              # From database stores.id
NEXT_PUBLIC_STORE_NAME=Fashion Boutique
NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1
```

**Optional variables**:
```env
NEXT_PUBLIC_STRIPE_KEY=pk_live_xxxxx
NEXT_PUBLIC_GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
NEXT_PUBLIC_FACEBOOK_PIXEL_ID=xxxxxxxxxxxx
NEXT_PUBLIC_SENTRY_DSN=https://xxx@sentry.io/xxx
```

### 3. **Client Isolation**

Each client storefront should:
- ✅ Have its own git repository
- ✅ Have separate environment variables
- ✅ Use unique store_id for API calls
- ✅ Have independent deployment pipeline
- ✅ Can be customized without affecting other clients

### 4. **Version Management**

Tag releases for each client:

```powershell
git tag v1.0.0 -m "Initial launch"
git tag v1.1.0 -m "Add custom homepage"
git push origin --tags
```

---

## Troubleshooting

### Issue: "Store not found" error

**Cause**: Store ID not configured or doesn't exist in database.

**Solution**:
1. Verify store exists: `SELECT * FROM stores WHERE id = 2;`
2. Check `.env.local` has correct `NEXT_PUBLIC_STORE_ID`
3. Restart dev server: `npm run dev`

### Issue: No products showing

**Cause**: Products not associated with this store_id.

**Solution**:
```sql
-- Verify products exist for this store
SELECT id, name, store_id FROM products WHERE store_id = 2;

-- If none exist, create test products via admin panel
-- or insert test data with correct store_id
```

### Issue: API calls failing

**Cause**: API URL incorrect or CORS issue.

**Solution**:
1. Verify `NEXT_PUBLIC_API_URL` in `.env.local`
2. Check backend CORS configuration allows storefront origin
3. Test API directly: `curl http://localhost:8000/api/v1/stores/2`

### Issue: Build fails

**Cause**: TypeScript errors or missing dependencies.

**Solution**:
```powershell
# Clear cache and reinstall
Remove-Item -Recurse -Force node_modules, .next
npm install
npm run build
```

---

## Example: Complete Client Setup

Here's a full example from start to finish:

```powershell
# 1. Create store in database (via admin panel or SQL)
# Assume we get store_id = 5 for "Organic Foods"

# 2. Run creation script
cd c:\poc\e-com
.\scripts\create-client-store.ps1 "Organic Foods Store" 5

# 3. Enter the storefront
cd client-organic-foods-store

# 4. Install dependencies
npm install

# 5. Customize theme
# Edit src/config/theme.ts with green/organic colors

# 6. Add branding
# Copy organic-logo.png to public/images/logo.png

# 7. Test locally
npm run dev
# Visit http://localhost:3000

# 8. Create GitHub repository
# On GitHub: Create new repo "organic-foods-storefront"

# 9. Push code
git remote add origin https://github.com/client-org/organic-foods-storefront.git
git push -u origin main
git push origin development staging

# 10. Deploy to Vercel
vercel
# Configure environment variables in Vercel dashboard
vercel --prod

# 11. Configure custom domain
# In Vercel: Add domain organic-foods.com
# Update DNS: CNAME to cname.vercel-dns.com

# 12. Launch! 🚀
```

---

## Support & Resources

- **Template Documentation**: `storefront-template/README.md`
- **API Reference**: `docs/API-REFERENCE.md`
- **Deployment Guide**: `docs/23-deployment-guide.md`
- **Performance Optimization**: `docs/24-performance-optimization.md`
- **Security Checklist**: `docs/25-security-audit.md`

For technical support, contact the platform team.

---

**Next**: After creating the storefront, proceed to [docs/23-deployment-guide.md](23-deployment-guide.md) for production deployment instructions.
