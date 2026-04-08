# Docker Setup Guide

## Overview

Complete Docker Compose setup for the multi-tenant e-commerce platform. Containerizes all services for easy development and deployment.

---

## 📦 Services Included

| Service | Container | Port | Description |
|---------|-----------|------|-------------|
| **MySQL** | ecom-mysql | 3306 | MySQL 8.0 database |
| **Redis** | ecom-redis | 6379 | Cache, sessions, queues |
| **Backend** | ecom-backend | 8000 | Laravel 11 API |
| **Queue Worker** | ecom-queue-worker | - | Background job processor |
| **Admin Panel** | ecom-admin | 5173 | React admin dashboard |
| **Nginx** | ecom-nginx | 80, 443 | Reverse proxy (production) |
| **PhpMyAdmin** | ecom-phpmyadmin | 8080 | Database management (optional) |

---

## 🚀 Quick Start

### Prerequisites

- **Docker** 20.10+ installed
- **Docker Compose** 2.0+ installed
- At least 4GB RAM available for containers

### 1. Initial Setup

```powershell
# Navigate to project root
cd c:\poc\e-com

# Copy environment file
Copy-Item .env.docker .env

# Generate Laravel application key
docker-compose run --rm backend php artisan key:generate

# Update .env with the generated key
```

### 2. Start All Services

```powershell
# Start all containers
docker-compose up -d

# View logs
docker-compose logs -f

# Check container status
docker-compose ps
```

### 3. Run Database Migrations

```powershell
# Run migrations
docker-compose exec backend php artisan migrate

# Seed database
docker-compose exec backend php artisan db:seed
```

### 4. Access Services

- **Backend API**: http://localhost:8000
- **Admin Panel**: http://localhost:5173
- **PhpMyAdmin**: http://localhost:8080 (if enabled)
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

---

## 🛠️ Common Commands

### Container Management

```powershell
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart a specific service
docker-compose restart backend

# View logs for all services
docker-compose logs -f

# View logs for specific service
docker-compose logs -f backend

# Check container status
docker-compose ps

# Stop and remove all containers, networks, and volumes
docker-compose down -v
```

### Backend (Laravel) Commands

```powershell
# Run artisan commands
docker-compose exec backend php artisan <command>

# Examples:
docker-compose exec backend php artisan migrate
docker-compose exec backend php artisan db:seed
docker-compose exec backend php artisan tinker
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan queue:work
docker-compose exec backend php artisan test

# Install Composer dependencies
docker-compose exec backend composer install

# Run PHPUnit tests
docker-compose exec backend php artisan test --filter=AuthenticationTest
```

### Admin Panel Commands

```powershell
# Install npm packages
docker-compose exec admin-panel npm install

# Run development server (already running)
docker-compose exec admin-panel npm run dev

# Build for production
docker-compose exec admin-panel npm run build

# Run type checking
docker-compose exec admin-panel npm run type-check

# Run linter
docker-compose exec admin-panel npm run lint
```

### Database Commands

```powershell
# Access MySQL CLI
docker-compose exec mysql mysql -u root -p
# Password: root_secret (from .env)

# Backup database
docker-compose exec mysql mysqldump -u root -p ecommerce_platform > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u root -p ecommerce_platform < backup.sql

# Access via PhpMyAdmin
# Open http://localhost:8080
# Username: root
# Password: root_secret
```

### Redis Commands

```powershell
# Access Redis CLI
docker-compose exec redis redis-cli -a redis_secret

# View all keys
docker-compose exec redis redis-cli -a redis_secret KEYS '*'

# Flush all cache
docker-compose exec redis redis-cli -a redis_secret FLUSHALL
```

---

## 🔧 Configuration

### Environment Variables

All configuration is in `.env` file (copied from `.env.docker`):

```env
# Database
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ecommerce_platform
DB_USERNAME=ecommerce
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=redis_secret
REDIS_PORT=6379

# Application
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:your-key-here
```

### Port Conflicts

If ports are already in use, modify `docker-compose.yml`:

```yaml
services:
  mysql:
    ports:
      - "3307:3306"  # Change from 3306 to 3307
```

### Volume Persistence

Data is persisted in Docker volumes:

```yaml
volumes:
  mysql_data:       # MySQL database files
  redis_data:       # Redis data
  backend_storage:  # Laravel storage (logs, cache, uploads)
```

**View volumes**:
```powershell
docker volume ls
```

**Remove volumes** (deletes data!):
```powershell
docker-compose down -v
```

---

## 🏗️ Development Workflow

### 1. First Time Setup

```powershell
# Clone repository
git clone <repo-url>
cd e-com

# Copy environment
Copy-Item .env.docker .env

# Start containers
docker-compose up -d

# Generate app key
docker-compose exec backend php artisan key:generate

# Run migrations
docker-compose exec backend php artisan migrate

# Seed database
docker-compose exec backend php artisan db:seed

# Install admin dependencies
docker-compose exec admin-panel npm install
```

### 2. Daily Development

```powershell
# Start containers
docker-compose up -d

# Check logs
docker-compose logs -f backend admin-panel

# Make code changes (files are mounted, changes reflect immediately)

# Run tests
docker-compose exec backend php artisan test

# Stop containers when done
docker-compose down
```

### 3. Database Changes

```powershell
# Create migration
docker-compose exec backend php artisan make:migration create_products_table

# Run migrations
docker-compose exec backend php artisan migrate

# Rollback
docker-compose exec backend php artisan migrate:rollback

# Fresh migrate (reset database)
docker-compose exec backend php artisan migrate:fresh --seed
```

---

## 📊 Production Setup

### Build for Production

```powershell
# Build production images
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build

# Start production stack
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### Production Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate strong `APP_KEY`
- [ ] Use secure database passwords
- [ ] Enable Nginx reverse proxy
- [ ] Configure SSL certificates
- [ ] Set up automated backups
- [ ] Configure monitoring (Sentry, UptimeRobot)
- [ ] Set up log rotation
- [ ] Configure firewall rules

---

## 🐞 Troubleshooting

### Container Won't Start

```powershell
# Check container logs
docker-compose logs <service-name>

# Check container status
docker ps -a

# Remove and recreate containers
docker-compose down
docker-compose up -d
```

### Database Connection Failed

```powershell
# Check if MySQL is healthy
docker-compose ps

# Check MySQL logs
docker-compose logs mysql

# Verify credentials in .env
cat .env | Select-String -Pattern "DB_"

# Test connection
docker-compose exec backend php artisan tinker
# Then run: DB::connection()->getPdo();
```

### Permission Errors (Laravel)

```powershell
# Fix storage permissions
docker-compose exec backend chmod -R 777 storage bootstrap/cache

# Or as root
docker-compose exec -u root backend chmod -R 777 storage bootstrap/cache
```

### Redis Connection Failed

```powershell
# Check Redis is running
docker-compose ps redis

# Test Redis connection
docker-compose exec redis redis-cli -a redis_secret ping
# Should return: PONG

# Clear Redis cache
docker-compose exec redis redis-cli -a redis_secret FLUSHALL
```

### Port Already in Use

```powershell
# Find process using port
netstat -ano | findstr :8000

# Kill process (if safe)
Stop-Process -Id <PID> -Force

# Or change port in docker-compose.yml
```

### Composer Install Fails

```powershell
# Clear Composer cache
docker-compose exec backend composer clear-cache

# Install with verbose output
docker-compose exec backend composer install -vvv

# Update Composer
docker-compose exec backend composer self-update
```

### Container Out of Memory

```powershell
# Increase Docker memory limit in Docker Desktop
# Settings → Resources → Memory (set to 4GB minimum)

# Or add to docker-compose.yml:
services:
  backend:
    mem_limit: 1g
```

---

## 🔒 Security Best Practices

### 1. Environment Variables

```env
# Never commit .env to git
# Use strong passwords
DB_PASSWORD=strong-random-password-here
REDIS_PASSWORD=another-strong-password

# Generate secure APP_KEY
APP_KEY=base64:random-32-character-string
```

### 2. Network Isolation

```yaml
# Services communicate via internal network
networks:
  ecom-network:
    driver: bridge
```

### 3. Production Hardening

```yaml
# Disable debug mode
APP_DEBUG=false

# Use production-optimized images
# Set memory limits
# Enable health checks
# Use read-only file systems where possible
```

---

## 📈 Performance Optimization

### 1. OPcache

Already enabled in `docker/php/php.ini`:

```ini
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
```

### 2. Redis Caching

```powershell
# Enable cache
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
docker-compose exec backend php artisan view:cache

# Clear cache
docker-compose exec backend php artisan cache:clear
```

### 3. Database Optimization

```sql
-- Add indexes via migrations
Schema::table('products', function (Blueprint $table) {
    $table->index(['store_id', 'id']);
    $table->index('sku');
});
```

---

## 🧪 Testing

### Run Tests in Docker

```powershell
# Run all tests
docker-compose exec backend php artisan test

# Run specific test suite
docker-compose exec backend php artisan test --filter=AuthenticationTest

# Run with coverage
docker-compose exec backend php artisan test --coverage

# Parallel testing
docker-compose exec backend php artisan test --parallel
```

---

## 📦 Building Images

### Build Custom Images

```powershell
# Build all services
docker-compose build

# Build specific service
docker-compose build backend

# Build with no cache
docker-compose build --no-cache

# Build production images
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build
```

### Tag and Push to Registry

```powershell
# Tag image
docker tag ecommerce_backend:latest myregistry.com/ecommerce-backend:1.0.0

# Push to registry
docker push myregistry.com/ecommerce-backend:1.0.0
```

---

## 🎯 Next Steps

After Docker setup:

1. **Access Admin Panel**: http://localhost:5173
2. **Login**: Use seeded credentials
3. **Create Store**: Add your first store via admin
4. **Add Products**: Create products for testing
5. **Test API**: http://localhost:8000/api/v1/products
6. **Create Storefront**: Use storefront creation script

---

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [Laravel in Docker](https://laravel.com/docs/deployment)
- [Production Deployment Guide](docs/23-deployment-guide.md)
- [Performance Optimization](docs/24-performance-optimization.md)

---

## 🆘 Support

For issues or questions:
1. Check logs: `docker-compose logs -f`
2. Restart services: `docker-compose restart`
3. Clean rebuild: `docker-compose down -v && docker-compose up -d --build`
4. Refer to troubleshooting section above

---

**Docker setup complete!** Your platform is now fully containerized. 🐳✨
