FROM php:8.2-fpm-alpine

# Install System Dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    libzip-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    oniguruma-dev

# Install PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo_sqlite \
    zip \
    intl \
    mbstring \
    opcache

# Configure PHP
COPY docker/config/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configure Nginx
COPY docker/config/nginx.conf /etc/nginx/http.d/default.conf
RUN mkdir -p /run/nginx

# Setup Supervisor
COPY docker/config/supervisord.conf /etc/supervisord.conf

# Set Working Directory
WORKDIR /var/www/html

# Copy Project
COPY . .

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/database.sqlite \
    && chmod -R 775 /var/www/html/storage \
    && chmod 664 /var/www/html/database.sqlite

# Expose Port
EXPOSE 80

# Start Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
