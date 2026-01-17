# --- Backend (Laravel 12) runtime ---
# Using PHP 8.5 with FPM on Alpine Linux (lightweight)
FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    libpq-dev \
    libzip-dev \
    oniguruma-dev \
    openssl \
    icu-dev \
    shadow \
    supervisor

RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip intl pcntl \
    && php -m | grep -q pcntl || (echo "ERROR: pcntl extension not loaded" && exit 1)

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy Laravel backend dependencies definition (composer.lock optional; composer install will create it if missing)
COPY backend/composer.json ./
RUN if [ -f composer.json ]; then composer install --no-dev --prefer-dist --no-interaction --no-scripts; fi

# Copy backend source code
COPY backend/. .

# Create log directories and set permissions (before switching user)
RUN mkdir -p /var/log/php /var/log/supervisor /etc/supervisor/conf.d \
  && chown -R www-data:www-data /var/log/php /var/log/supervisor

# Copy supervisor configuration (optional - for running processes via supervisor)
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/conf.d/ /etc/supervisor/conf.d/

# PHP configuration - show stack trace arguments in errors
RUN echo 'zend.exception_ignore_args = Off' > /usr/local/etc/php/conf.d/exceptions.ini

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Copy Xdebug configuration (will be conditionally enabled via entrypoint)
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini.source

# Copy entrypoint script for conditional Xdebug enabling
COPY docker/php/docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
RUN chmod +x /usr/local/bin/docker-php-entrypoint \
  && chmod 777 /usr/local/etc/php/conf.d

# Set up Laravel directories and permissions
RUN mkdir -p storage bootstrap/cache \
  && chown -R www-data:www-data storage bootstrap/cache \
  && chmod -R ug+rwx storage bootstrap/cache

USER www-data

EXPOSE 9000
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["php-fpm"]


