# Production Deployment Guide

## Overview

Step-by-step guide for deploying the multi-tenant e-commerce platform to production servers. This guide covers server setup, application deployment, and post-deployment verification.

**Prerequisites**:
- Ubuntu 22.04 LTS server
- Root or sudo access
- Domain names configured (api.yourdomain.com, admin.yourdomain.com)
- SSL certificates ready

---

## 1. Server Setup

### System Requirements

**Minimum (1-10 stores)**:
- 2 vCPU
- 4GB RAM
- 50GB SSD
- Cost: ~$20-40/month (DigitalOcean, Linode, Vultr)

**Recommended (10-50 stores)**:
- 4 vCPU
- 8GB RAM
- 100GB SSD
- Cost: ~$40-80/month

**Production (50+ stores)**:
- 8 vCPU  
- 16GB RAM
- 200GB SSD
- Cost: ~$80-160/month

### Initial Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y software-properties-common curl wget git unzip

# Create deployment user
sudo adduser deploy
sudo usermod -aG sudo deploy
sudo su - deploy
```

### Install PHP 8.2

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
    php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml \
    php8.2-bcmath php8.2-redis php8.2-intl

# Verify installation
php -v
# Should output: PHP 8.2.x
```

### Install Composer

```bash
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

### Install MySQL 8.0

```bash
# Install MySQL
sudo apt install -y mysql-server

# Secure installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p

mysql> CREATE DATABASE ecommerce_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
mysql> CREATE USER 'ecommerce_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
mysql> GRANT ALL PRIVILEGES ON ecommerce_prod.* TO 'ecommerce_user'@'localhost';
mysql> FLUSH PRIVILEGES;
mysql> EXIT;
```

### Install Redis

```bash
# Install Redis
sudo apt install -y redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf
# Set: maxmemory 256mb
# Set: maxmemory-policy allkeys-lru
# Set: requirepass YOUR_STRONG_PASSWORD

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server

# Test connection
redis-cli -a YOUR_STRONG_PASSWORD ping
# Should output: PONG
```

### Install Nginx

```bash
# Install Nginx
sudo apt install -y nginx

# Start and enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

### Install Node.js 20 (for admin panel)

```bash
# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify installation
node -v  # Should be v20+
npm -v
```

### Install Supervisor (for queue workers)

```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
```

---

## 2. Application Deployment

### Clone Repository

```bash
# Create application directory
sudo mkdir -p /var/www/html
sudo chown -R deploy:deploy /var/www/html

# Clone repository (assuming Git access)
cd /var/www/html
git clone https://github.com/yourusername/ecommerce-platform.git .

# Or upload via SCP/SFTP
```

### Backend Setup

```bash
cd /var/www/html/platform/backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy environment file
cp .env.example .env
nano .env
# Update with production values (see docs/22-production-configuration.md)

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed data (if needed)
php artisan db:seed --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Admin Panel Setup

```bash
cd /var/www/html/platform/admin-panel

# Install dependencies
npm ci

# Create production .env file
cp .env.example .env.production
nano .env.production
# Update with production API URL

# Build for production
npm run build

# The dist folder contains the built admin panel
```

### Storefront Template Setup

```bash
cd /var/www/html/storefront-template

# Install dependencies
npm ci

# Build for production
npm run build

# Start production server (or use PM2)
npm start
```

---

## 3. Configure Nginx

### Backend API Configuration

Create `/etc/nginx/sites-available/api.yourdomain.com`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.yourdomain.com;

    root /var/www/html/platform/backend/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml;

    # Client max body size (for file uploads)
    client_max_body_size 20M;

    # Logging
    access_log /var/log/nginx/api.yourdomain.com-access.log;
    error_log /var/log/nginx/api.yourdomain.com-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Block access to sensitive files
    location ~ /\.env {
        deny all;
    }

    location ~ /composer\.(json|lock) {
        deny all;
    }
}
```

### Admin Panel Configuration

Create `/etc/nginx/sites-available/admin.yourdomain.com`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name admin.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name admin.yourdomain.com;

    root /var/www/html/platform/admin-panel/dist;
    index index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/admin.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/admin.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip compression
    gzip on;
    gzip_static on;
    gzip_vary on;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # SPA routing
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Logging
    access_log /var/log/nginx/admin.yourdomain.com-access.log;
    error_log /var/log/nginx/admin.yourdomain.com-error.log;
}
```

### Enable Sites

```bash
# Enable sites
sudo ln -s /etc/nginx/sites-available/api.yourdomain.com /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/admin.yourdomain.com /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## 4. SSL Certificates (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificates for API
sudo certbot --nginx -d api.yourdomain.com

# Obtain certificates for Admin Panel
sudo certbot --nginx -d admin.yourdomain.com

# Auto-renewal is configured by default
# Test auto-renewal
sudo certbot renew --dry-run
```

---

## 5. Configure Queue Workers

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/platform/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/html/platform/backend/storage/logs/worker.log
stopwaitsecs=3600
startsecs=0
```

Start workers:
```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start laravel-worker:*

# Check status
sudo supervisorctl status
```

---

## 6. Setup Cron Jobs

```bash
# Edit crontab
sudo crontab -u www-data -e

# Add Laravel scheduler
* * * * * cd /var/www/html/platform/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 7. Firewall Configuration

```bash
# Install UFW
sudo apt install -y ufw

# Allow SSH
sudo ufw allow OpenSSH

# Allow HTTP/HTTPS
sudo ufw allow 'Nginx Full'

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

---

## 8. Monitoring Setup

### Install Sentry (Error Tracking)

Already configured in Laravel. Just set in `.env`:
```env
SENTRY_LARAVEL_DSN=https://xxxxx@xxxxx.ingest.sentry.io/xxxxx
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### Setup UptimeRobot

1. Go to [UptimeRobot.com](https://uptimerobot.com)
2. Create monitors:
   - API: `https://api.yourdomain.com/health`
   - Admin: `https://admin.yourdomain.com`
3. Configure alert contacts (email, Slack)

### Health Check Endpoint

Already configured at `/health`. Test:
```bash
curl https://api.yourdomain.com/health
# Should return: {"status":"ok","timestamp":"..."}
```

---

## 9. Database Backup

### Automated MySQL Backup Script

Create `/usr/local/bin/backup-db.sh`:

```bash
#!/bin/bash

# Configuration
DB_NAME="ecommerce_prod"
DB_USER="ecommerce_user"
DB_PASS="YOUR_PASSWORD"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Create backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Delete backups older than 7 days
find $BACKUP_DIR -type f -name "*.sql.gz" -mtime +7 -delete

echo "Backup completed: backup_$DATE.sql.gz"
```

Make executable:
```bash
sudo chmod +x /usr/local/bin/backup-db.sh
```

Add to cron (daily at 2 AM):
```bash
sudo crontab -e
# Add:
0 2 * * * /usr/local/bin/backup-db.sh >> /var/log/mysql-backup.log 2>&1
```

---

## 10. Post-Deployment Verification

### Check Services

```bash
# Check PHP-FPM
sudo systemctl status php8.2-fpm

# Check Nginx
sudo systemctl status nginx

# Check MySQL
sudo systemctl status mysql

# Check Redis
sudo systemctl status redis-server

# Check Queue Workers
sudo supervisorctl status laravel-worker:*
```

### Test Endpoints

```bash
# Health check
curl https://api.yourdomain.com/health

# API endpoint (should return 401 unauthorized)
curl https://api.yourdomain.com/api/v1/products

# Admin panel
curl -I https://admin.yourdomain.com
# Should return 200 OK
```

### Check Logs

```bash
# Application logs
tail -f /var/www/html/platform/backend/storage/logs/laravel.log

# Nginx access logs
tail -f /var/log/nginx/api.yourdomain.com-access.log

# Nginx error logs
tail -f /var/log/nginx/api.yourdomain.com-error.log

# Worker logs
tail -f /var/www/html/platform/backend/storage/logs/worker.log
```

### Performance Test

```bash
# Test API response time
curl -w "@curl-format.txt" -o /dev/null -s https://api.yourdomain.com/health

# Create curl-format.txt:
cat > curl-format.txt << EOF
    time_namelookup:  %{time_namelookup}\n
       time_connect:  %{time_connect}\n
    time_appconnect:  %{time_appconnect}\n
      time_redirect:  %{time_redirect}\n
   time_pretransfer:  %{time_pretransfer}\n
 time_starttransfer:  %{time_starttransfer}\n
                    ----------\n
         time_total:  %{time_total}\n
EOF
```

---

## 11. Rollback Procedure

### Quick Rollback

```bash
# Revert to previous commit
cd /var/www/html/platform/backend
git checkout main
git reset --hard HEAD~1

# Reinstall dependencies
composer install --no-dev --optimize-autoloader

# Revert migrations (if needed)
php artisan migrate:rollback --step=1

# Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart workers
sudo supervisorctl restart laravel-worker:*
```

### Database Rollback

```bash
# Restore from backup
cd /var/backups/mysql
gunzip backup_YYYYMMDD_HHMMSS.sql.gz
mysql -u ecommerce_user -p ecommerce_prod < backup_YYYYMMDD_HHMMSS.sql
```

---

## 12. Zero-Downtime Deployment

### Using Envoyer (Laravel Deployment Tool)

1. Sign up at [Envoyer.io](https://envoyer.io)
2. Connect your server via SSH
3. Configure deployment script
4. Enable zero-downtime deployments with symlinks

### Manual Zero-Downtime

```bash
# Deploy to new directory
cd /var/www/html
git clone https://github.com/yourusername/ecommerce-platform.git release-$(date +%Y%m%d%H%M%S)
cd release-$(date +%Y%m%d%H%M%S)/platform/backend

# Install and optimize
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Switch symlink
sudo ln -sfn /var/www/html/release-$(date +%Y%m%d%H%M%S) /var/www/html/current

# Update Nginx root to /var/www/html/current/platform/backend/public

# Restart workers
sudo supervisorctl restart laravel-worker:*

# Reload PHP-FPM
sudo systemctl reload php8.2-fpm
```

---

## 13. Troubleshooting

### Issue: 502 Bad Gateway

**Cause**: PHP-FPM not running or misconfigured

**Solution**:
```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Check Nginx error log
sudo tail -f /var/log/nginx/api.yourdomain.com-error.log
```

### Issue: 500 Internal Server Error

**Cause**: Application error or misconfiguration

**Solution**:
```bash
# Check Laravel logs
tail -f /var/www/html/platform/backend/storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data storage bootstrap/cache

# Clear caches
php artisan optimize:clear
```

### Issue: Database Connection Failed

**Cause**: Wrong credentials or MySQL not running

**Solution**:
```bash
# Check MySQL status
sudo systemctl status mysql

# Test connection
mysql -u ecommerce_user -p -h localhost ecommerce_prod

# Check .env credentials
cat /var/www/html/platform/backend/.env | grep DB_
```

### Issue: Queue Jobs Not Processing

**Cause**: Workers not running

**Solution**:
```bash
# Check worker status
sudo supervisorctl status laravel-worker:*

# Restart workers
sudo supervisorctl restart laravel-worker:*

# Check worker logs
tail -f /var/www/html/platform/backend/storage/logs/worker.log
```

---

## 14. Deployment Checklist

### Pre-Deployment
- [ ] Backup production database
- [ ] Test deployment on staging environment
- [ ] Run all tests locally: `php artisan test`
- [ ] Update version number in `.env`
- [ ] Prepare rollback plan
- [ ] Schedule deployment during low-traffic period
- [ ] Notify team about deployment

### During Deployment
- [ ] Enable maintenance mode: `php artisan down`
- [ ] Pull latest code: `git pull origin main`
- [ ] Install dependencies: `composer install --no-dev`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Rebuild caches: `php artisan config:cache`, `route:cache`, `view:cache`
- [ ] Restart queue workers: `sudo supervisorctl restart laravel-worker:*`
- [ ] Restart PHP-FPM: `sudo systemctl reload php8.2-fpm`
- [ ] Disable maintenance mode: `php artisan up`

### Post-Deployment
- [ ] Test critical user flows (login, create order, etc.)
- [ ] Monitor error logs for 30 minutes
- [ ] Check queue workers are processing jobs
- [ ] Test API endpoints with Postman
- [ ] Verify admin panel loads correctly
- [ ] Monitor Sentry for new errors
- [ ] Check UptimeRobot status
- [ ] Announce deployment completion to team

---

## 15. Production Maintenance Schedule

### Daily
- Monitor error logs
- Check queue worker status
- Review Sentry errors

### Weekly
- Review slow query log
- Check disk space usage
- Clean up old log files
- Monitor database size

### Monthly
- Update dependencies: `composer update`
- Optimize database tables: `mysqlcheck -o -u user -p database`
- Review and rotate SSL certificates
- Review backup retention policy
- Performance audit

---

## Related Documentation
- [docs/22-production-configuration.md](22-production-configuration.md) - Configuration guide
- [docs/21-monitoring-strategy.md](21-monitoring-strategy.md) - Monitoring setup
- [docs/08-scalability.md](08-scalability.md) - Scaling strategies
