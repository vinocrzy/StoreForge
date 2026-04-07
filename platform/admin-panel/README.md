# E-Commerce Admin Panel

React TypeScript admin dashboard for managing multi-tenant e-commerce platform.

## Features

- **Authentication**: Phone/email login with JWT tokens
- **Multi-Store Support**: Tenant-aware API integration  
- **Modern UI**: Ant Design component library
- **State Management**: Redux Toolkit with RTK Query
- **Routing**: React Router with protected routes
- **TypeScript**: Full type safety

## Tech Stack

- React 19 + TypeScript 6
- Vite 8 (build tool)
- Ant Design 6 (UI)
- Redux Toolkit 2 + RTK Query
- React Router 7
- Axios

## Quick Start

```bash
# Install dependencies
npm install

# Start dev server
npm run dev
```

Access at: http://localhost:5173

**Default login**: admin@ecommerce-platform.com / password

## API Configuration

Environment variables in `.env`:

```env
VITE_API_URL=http://localhost:8000/api/v1
```

## Project Structure

```
src/
├── components/     # Reusable components
├── layouts/        # Layout components (MainLayout)
├── pages/          # Page components (Login, Dashboard)
├── services/       # API services (auth, apiClient)
├── store/          # Redux store + slices
└── types/          # TypeScript types
```

## Features

✅ **Implemented**:
- Authentication (login/logout)
- Protected routes
- Main layout with sidebar
- Dashboard page
- Redux + RTK Query setup
- Multi-store tenant support

🚧 **Coming Soon**:
- Product management UI
- Order management UI
- Customer management UI
- Inventory management UI

## Development

```bash
npm run dev      # Dev server
npm run build    # Production build
npm run preview  # Preview build
npm run lint     # Lint code
```

## Requirements

- Node.js 18+
- Backend API running at http://localhost:8000

See full documentation in project wiki.
