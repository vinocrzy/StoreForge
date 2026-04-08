# Multi-stage build for Laravel Backend
FROM php:8.2-fpm as base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/appuser appuser
RUN mkdir -p /home/appuser/.composer && \
    chown -R appuser:appuser /home/appuser

# Copy application files
COPY --chown=appuser:appuser . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R appuser:appuser /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

USER appuser

# Expose port 8000 for artisan serve
EXPOSE 8000

# Development stage
FROM base as development

USER root
RUN composer install --optimize-autoloader --no-interaction
USER appuser

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# Production stage
FROM base as production

USER root

# Install production dependencies only
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Optimize for production
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

USER appuser

# Use PHP-FPM in production
CMD ["php-fpm"]
