#!/bin/bash

# Setup Multi-Repository Structure for E-Commerce Platform
# This script initializes the recommended repository structure

set -e  # Exit on error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

echo "==========================================="
echo "E-Commerce Platform Repository Setup"
echo "==========================================="
echo ""
echo "This will create:"
echo "  1. platform/ (Backend + Admin Panel)"
echo "  2. storefront-template/ (Base template)"
echo ""
echo "Root directory: $ROOT_DIR"
echo ""

read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Setup cancelled."
    exit 1
fi

# ============================================
# Step 1: Initialize Platform Repository
# ============================================

echo ""
echo "📦 Step 1: Initializing Platform Repository..."
echo ""

PLATFORM_DIR="$ROOT_DIR/platform"
mkdir -p "$PLATFORM_DIR"
cd "$PLATFORM_DIR"

# Initialize git if not already initialized
if [ ! -d ".git" ]; then
    git init
    echo "✓ Git initialized in platform/"
else
    echo "✓ Git already initialized in platform/"
fi

# Create .gitignore
cat > .gitignore << 'EOF'
# Backend (Laravel)
/backend/.env
/backend/.env.backup
/backend/.env.production
/backend/vendor/
/backend/node_modules/
/backend/storage/*.key
/backend/storage/logs/
/backend/storage/framework/cache/
/backend/storage/framework/sessions/
/backend/storage/framework/testing/
/backend/storage/framework/views/
/backend/bootstrap/cache/
/backend/public/hot
/backend/public/storage
/backend/.phpunit.result.cache
/backend/Homestead.json
/backend/Homestead.yaml
/backend/auth.json
/backend/npm-debug.log
/backend/yarn-error.log

# Admin Panel (React)
/admin-panel/node_modules/
/admin-panel/.env
/admin-panel/.env.local
/admin-panel/.env.development.local
/admin-panel/.env.test.local
/admin-panel/.env.production.local
/admin-panel/dist/
/admin-panel/build/
/admin-panel/.vite/
/admin-panel/npm-debug.log*
/admin-panel/yarn-debug.log*
/admin-panel/yarn-error.log*

# IDE
.vscode/
.idea/
*.swp
*.swo
*~

# OS
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db

# Logs
*.log
logs/
EOF

echo "✓ Created .gitignore"

# Create README
cat > README.md << 'EOF'
# E-Commerce Platform Core

Multi-tenant e-commerce platform with shared backend and admin panel.

## Components

- **backend/** - Laravel 11 REST API
- **admin-panel/** - React 18 Admin Dashboard

## Quick Start

### Backend Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Admin Panel Setup
```bash
cd admin-panel
npm install
npm run dev
```

## Documentation

See `/docs` folder in root for complete documentation.

## Version

Current version: 1.0.0
EOF

echo "✓ Created README.md"

# Initial commit
git add .
if git diff-index --quiet HEAD 2>/dev/null; then
    git commit -m "chore: initialize platform repository structure" || echo "✓ Platform repo already committed"
else
    git commit -m "chore: initialize platform repository structure"
fi

echo "✓ Platform repository initialized"

# ============================================
# Step 2: Initialize Storefront Template
# ============================================

echo ""
echo "📦 Step 2: Initializing Storefront Template..."
echo ""

TEMPLATE_DIR="$ROOT_DIR/storefront-template"
mkdir -p "$TEMPLATE_DIR"
cd "$TEMPLATE_DIR"

# Initialize git if not already initialized
if [ ! -d ".git" ]; then
    git init
    echo "✓ Git initialized in storefront-template/"
else
    echo "✓ Git already initialized in storefront-template/"
fi

# Create .gitignore
cat > .gitignore << 'EOF'
# Dependencies
node_modules/
/.pnp
.pnp.js

# Testing
/coverage

# Next.js
/.next/
/out/
.vercel

# Production
/build

# Environment files
.env
.env.local
.env.development.local
.env.test.local
.env.production.local

# Debug
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# IDE
.vscode/
.idea/

# OS
.DS_Store
*.pem

# TypeScript
*.tsbuildinfo
next-env.d.ts
EOF

echo "✓ Created .gitignore"

# Create README
cat > README.md << 'EOF'
# E-Commerce Storefront Template

Base template for client storefronts using Next.js 14 with static export.

## 🚀 Quick Start for New Client

### 1. Clone this template
```bash
cd c:\poc\e-com
git clone storefront-template client-your-store-name
cd client-your-store-name
```

### 2. Remove template git history and initialize fresh
```bash
rm -rf .git
git init
```

### 3. Configure store
```bash
# Copy environment template
cp .env.template .env.local

# Edit .env.local and set:
# - NEXT_PUBLIC_STORE_ID
# - NEXT_PUBLIC_STORE_NAME
# - NEXT_PUBLIC_STRIPE_KEY (from client)
```

### 4. Customize theme
Edit `theme/config.ts` with client's branding:
- Colors
- Fonts
- Logo URLs
- Layout preferences

### 5. Install and run
```bash
npm install
npm run dev
```

### 6. Build and deploy
```bash
npm run build
# Static files will be in 'out/' directory
```

## 📖 Documentation

- See `CUSTOMIZATION.md` for detailed customization guide
- See `/docs` in root for architecture documentation

## 🎨 What to Customize

### Must Customize
- [ ] `.env.local` - Store configuration
- [ ] `theme/config.ts` - Brand colors, fonts, logo
- [ ] `public/` - Replace favicon and images

### Optional Customization
- [ ] `components/` - Modify UI components
- [ ] `app/` - Customize page layouts
- [ ] `styles/` - Add custom CSS

### Do NOT Modify
- ❌ `core/` - Shared logic (if separated)
- ❌ API client internals

## 🔧 Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run start` - Preview production build
- `npm run lint` - Run ESLint

## 📦 Tech Stack

- Next.js 14 (App Router)
- TypeScript
- Tailwind CSS
- Static Export (SSG)

## 🆕 Template Updates

To get latest template improvements:

```bash
# Add template as remote (one-time)
git remote add template ../storefront-template

# Fetch updates
git fetch template

# Merge updates (may need to resolve conflicts)
git merge template/main
```

## 📝 Version

Template version: 1.0.0
EOF

echo "✓ Created README.md"

# Create CUSTOMIZATION.md
cat > CUSTOMIZATION.md << 'EOF'
# Storefront Customization Guide

Complete guide for customizing this storefront for a new client.

## Prerequisites

- Node.js 18+ installed
- Git installed
- Store ID from admin panel
- Client's branding assets (logo, colors, fonts)

## Step-by-Step Customization

### 1. Environment Configuration

Create `.env.local`:

```env
# Store Identification
NEXT_PUBLIC_STORE_ID=1
NEXT_PUBLIC_STORE_NAME=Your Store Name

# API Configuration
NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1

# Payment Gateway
NEXT_PUBLIC_STRIPE_KEY=pk_live_xxx

# Optional: Analytics
NEXT_PUBLIC_GA_ID=
NEXT_PUBLIC_GTM_ID=
```

### 2. Theme Configuration

Edit `theme/config.ts`:

```typescript
export const themeConfig = {
  // Store info
  store_id: process.env.NEXT_PUBLIC_STORE_ID,
  
  // Branding
  branding: {
    name: 'Your Store Name',
    logo: '/logo.png',
    favicon: '/favicon.ico',
    tagline: 'Your tagline here',
  },
  
  // Colors (use client's brand colors)
  colors: {
    primary: '#FF6B6B',      // Main brand color
    secondary: '#4ECDC4',    // Secondary brand color
    accent: '#FFE66D',       // Accent color
    background: '#FFFFFF',   // Background
    text: '#2C3E50',         // Text color
    error: '#E74C3C',        // Error state
    success: '#2ECC71',      // Success state
  },
  
  // Typography
  typography: {
    fontFamily: '"Inter", sans-serif',
    headingFamily: '"Playfair Display", serif',
  },
  
  // Layout
  layout: {
    headerStyle: 'centered',  // 'centered' | 'left' | 'minimal'
    footerColumns: 4,
    maxWidth: '1440px',
  },
  
  // Features
  features: {
    wishlist: true,
    reviews: true,
    quickView: true,
    sizeGuide: true,
  },
};
```

### 3. Assets

Replace default assets in `public/`:

```
public/
├── logo.png           # Store logo (recommended: 200x60px PNG)
├── logo-white.png     # White version for dark backgrounds
├── favicon.ico        # 32x32 favicon
├── icon.png           # 512x512 PWA icon
└── og-image.png       # 1200x630 Open Graph image
```

### 4. Color Customization

Colors are applied via CSS variables. The theme system automatically generates:

```css
:root {
  --color-primary: /* from theme.colors.primary */;
  --color-secondary: /* from theme.colors.secondary */;
  /* ... */
}
```

Use in components:
```tsx
<button className="bg-primary text-white">
  Buy Now
</button>
```

### 5. Typography

To use custom fonts, add to `app/layout.tsx`:

```typescript
import { Inter, Playfair_Display } from 'next/font/google';

const inter = Inter({ subsets: ['latin'] });
const playfair = Playfair_Display({ subsets: ['latin'] });
```

### 6. Component Customization

To customize a component:

1. Copy from `components/shared/` to `components/custom/`
2. Modify as needed
3. Update imports to use custom version

Example:
```bash
cp components/shared/ProductCard.tsx components/custom/ProductCard.tsx
# Edit components/custom/ProductCard.tsx
```

### 7. Page Customization

Pages are in `app/` directory:

```
app/
├── page.tsx              # Home page
├── products/
│   ├── page.tsx          # Product listing
│   └── [slug]/
│       └── page.tsx      # Product detail
├── cart/
│   └── page.tsx          # Shopping cart
└── checkout/
    └── page.tsx          # Checkout
```

Customize page layouts and content as needed.

### 8. SEO Configuration

Edit `app/layout.tsx` for global SEO:

```typescript
export const metadata: Metadata = {
  title: {
    template: '%s | Your Store Name',
    default: 'Your Store Name - Tagline',
  },
  description: 'Your store description',
  keywords: ['keyword1', 'keyword2'],
  openGraph: {
    title: 'Your Store Name',
    description: 'Your store description',
    images: ['/og-image.png'],
  },
};
```

### 9. Testing

Before deploying:

```bash
# Run development server
npm run dev

# Test on http://localhost:3000
# Verify:
# - Logo and branding correct
# - Colors applied
# - All pages load
# - API connection works
# - Cart and checkout functional

# Build for production
npm run build

# Preview production build
npm run start
```

### 10. Deployment

#### Deploy to Vercel

```bash
# Install Vercel CLI
npm i -g vercel

# Deploy
vercel

# Or deploy to production
vercel --prod
```

#### Deploy to Netlify

```bash
# Install Netlify CLI
npm i -g netlify-cli

# Build
npm run build

# Deploy
netlify deploy --dir=out --prod
```

#### Static Export to Server

```bash
# Build
npm run build

# Upload 'out' directory to web server
# Configure web server to serve index.html files
```

## Advanced Customization

### Custom Components

Create new components in `components/custom/`:

```typescript
// components/custom/SpecialBanner.tsx
export function SpecialBanner() {
  return (
    <div className="bg-primary text-white p-4">
      Holiday Sale - 50% Off!
    </div>
  );
}
```

Use in pages:
```typescript
import { SpecialBanner } from '@/components/custom/SpecialBanner';
```

### Custom Pages

Add new pages in `app/`:

```
app/
└── about/
    └── page.tsx      # New about page
```

### Custom Styles

Add custom CSS in `styles/custom.css`:

```css
/* Special client-specific styles */
.special-banner {
  /* custom styles */
}
```

Import in `app/layout.tsx`:
```typescript
import '@/styles/custom.css';
```

## Troubleshooting

### Store ID not working
- Verify `NEXT_PUBLIC_STORE_ID` in `.env.local`
- Check store is active in admin panel
- Restart dev server after changing .env files

### API connection failed
- Verify `NEXT_PUBLIC_API_URL` is correct
- Check network/CORS settings
- Verify store has API access enabled

### Build fails
- Check for TypeScript errors: `npm run type-check`
- Verify all dependencies installed: `npm install`
- Check Next.js config: `next.config.js`

### Images not loading
- Ensure images in `public/` directory
- Use proper paths: `/logo.png` not `./logo.png`
- Check Next.js image configuration

## Best Practices

✅ **Do**:
- Keep `.env.local` secure (never commit)
- Test thoroughly before deployment
- Document custom changes
- Use Git branches for features
- Keep theme config centralized

❌ **Don't**:
- Hardcode store-specific values
- Modify core library files
- Commit sensitive keys
- Skip testing
- Deploy without building first

## Support

For issues or questions:
- Check documentation in `/docs`
- Review template README
- Contact development team

## Checklist

Before going live:

- [ ] Store ID configured
- [ ] Theme colors applied
- [ ] Logo and favicon replaced
- [ ] All branding updated
- [ ] SEO metadata set
- [ ] Pages tested
- [ ] Cart tested
- [ ] Checkout tested
- [ ] Payment gateway tested
- [ ] Mobile responsive verified
- [ ] Cross-browser tested
- [ ] Performance optimized
- [ ] SSL certificate active
- [ ] Custom domain configured
- [ ] Analytics integrated
- [ ] Backup strategy in place

## Template Version

This customization guide is for template version: 1.0.0
EOF

echo "✓ Created CUSTOMIZATION.md"

# Create .env.template
cat > .env.template << 'EOF'
# Store Configuration
NEXT_PUBLIC_STORE_ID=
NEXT_PUBLIC_STORE_NAME=

# API Configuration
NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1

# Payment Gateway (Get from client)
NEXT_PUBLIC_STRIPE_KEY=

# Optional: Analytics
NEXT_PUBLIC_GA_ID=
NEXT_PUBLIC_GTM_ID=

# Optional: Feature Flags
NEXT_PUBLIC_ENABLE_WISHLIST=true
NEXT_PUBLIC_ENABLE_REVIEWS=true
EOF

echo "✓ Created .env.template"

# Initial commit
git add .
if git diff-index --quiet HEAD 2>/dev/null; then
    git commit -m "chore: initialize storefront template" || echo "✓ Template repo already committed"
else
    git commit -m "chore: initialize storefront template"
fi

echo "✓ Storefront template initialized"

# ============================================
# Step 3: Create Helper Scripts
# ============================================

echo ""
echo "📦 Step 3: Creating helper scripts..."
echo ""

SCRIPTS_DIR="$ROOT_DIR/scripts"
mkdir -p "$SCRIPTS_DIR"

# Create client store creation script
cat > "$SCRIPTS_DIR/create-client-store.sh" << 'EOF'
#!/bin/bash

# Create New Client Storefront
# Usage: ./create-client-store.sh "Client Name" store_id

set -e

if [ $# -ne 2 ]; then
    echo "Usage: $0 'Client Name' store_id"
    echo "Example: $0 'Fashion Store' 1"
    exit 1
fi

CLIENT_NAME="$1"
STORE_ID="$2"
FOLDER_NAME="client-$(echo "$CLIENT_NAME" | tr '[:upper:]' '[:lower:]' | tr ' ' '-')"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
TEMPLATE_DIR="$ROOT_DIR/storefront-template"
CLIENT_DIR="$ROOT_DIR/$FOLDER_NAME"

echo "============================================"
echo "Creating Client Storefront"
echo "============================================"
echo ""
echo "Client: $CLIENT_NAME"
echo "Store ID: $STORE_ID"
echo "Directory: $FOLDER_NAME"
echo ""

# Check if template exists
if [ ! -d "$TEMPLATE_DIR" ]; then
    echo "❌ Error: Template not found at $TEMPLATE_DIR"
    exit 1
fi

# Check if client directory already exists
if [ -d "$CLIENT_DIR" ]; then
    echo "❌ Error: $CLIENT_DIR already exists"
    exit 1
fi

# Clone template
echo "📋 Cloning template..."
cp -r "$TEMPLATE_DIR" "$CLIENT_DIR"
cd "$CLIENT_DIR"

# Remove template git history
rm -rf .git

# Initialize fresh git repo
git init

# Create .env.local
echo "📝 Creating .env.local..."
cat > .env.local << ENVEOF
# Store Configuration
NEXT_PUBLIC_STORE_ID=$STORE_ID
NEXT_PUBLIC_STORE_NAME=$CLIENT_NAME

# API Configuration
NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1

# Payment Gateway (Update with client's key)
NEXT_PUBLIC_STRIPE_KEY=

# Optional: Analytics
NEXT_PUBLIC_GA_ID=
NEXT_PUBLIC_GTM_ID=
ENVEOF

# Update package.json name
if [ -f "package.json" ]; then
    sed -i.bak "s/\"name\": \"[^\"]*\"/\"name\": \"$FOLDER_NAME\"/" package.json
    rm package.json.bak 2>/dev/null || true
fi

# Initial commit
git add .
git commit -m "chore: initialize $CLIENT_NAME storefront"

echo ""
echo "✅ Client storefront created successfully!"
echo ""
echo "📁 Location: $CLIENT_DIR"
echo ""
echo "Next steps:"
echo "  1. cd $FOLDER_NAME"
echo "  2. Edit theme/config.ts with client branding"
echo "  3. Add Stripe key to .env.local"
echo "  4. Replace logo and favicon in public/"
echo "  5. npm install"
echo "  6. npm run dev"
echo ""
echo "To push to remote repository:"
echo "  git remote add origin <repository-url>"
echo "  git push -u origin main"
echo ""
EOF

chmod +x "$SCRIPTS_DIR/create-client-store.sh"
echo "✓ Created create-client-store.sh"

# ============================================
# Summary
# ============================================

echo ""
echo "==========================================="
echo "✅ Setup Complete!"
echo "==========================================="
echo ""
echo "Repository structure created:"
echo "  ✓ platform/ (Backend + Admin)"
echo "  ✓ storefront-template/ (Template)"
echo "  ✓ scripts/ (Helper scripts)"
echo ""
echo "Next steps:"
echo ""
echo "1. Set up Backend:"
echo "   cd platform"
echo "   composer create-project laravel/laravel backend"
echo ""
echo "2. Set up Admin Panel:"
echo "   cd platform"
echo "   npm create vite@latest admin-panel -- --template react-ts"
echo ""
echo "3. Set up Storefront Template:"
echo "   cd storefront-template"
echo "   npx create-next-app@latest . --typescript --tailwind --app"
echo ""
echo "4. Create your first client storefront:"
echo "   ./scripts/create-client-store.sh 'Test Store' 1"
echo ""
echo "📖 See docs/15-repository-structure.md for detailed guide"
echo ""
