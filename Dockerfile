# syntax=docker/dockerfile:1.7
#
# BMPC Project Management — production image for Dokploy.
#
# One self-contained container: nginx + PHP-FPM + the Laravel scheduler, all
# supervised by supervisord and listening on port 80. MySQL runs separately
# (a Dokploy MySQL service or any external server).
#
# Mount a persistent volume at /var/www/html/storage — uploaded documents,
# generated reports, and logs live there.

ARG PHP_VERSION=8.3
ARG NODE_VERSION=22

############################################################
# Base: PHP-FPM with the extensions the app needs
############################################################
FROM php:${PHP_VERSION}-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

# gd + zip: DomPDF and PhpSpreadsheet (PDF/Excel reports)
# pdo_mysql: database, intl/bcmath: formatting & money math, opcache: performance
RUN apk add --no-cache nginx supervisor su-exec tzdata \
    && install-php-extensions gd zip pdo_mysql intl bcmath exif opcache

WORKDIR /var/www/html

############################################################
# Composer dependencies (production only)
############################################################
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist

COPY . .
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --optimize --no-dev --no-interaction

############################################################
# Front-end assets (Vite + Tailwind)
############################################################
FROM node:${NODE_VERSION}-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
# Tailwind scans Laravel's pagination views for class names.
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN npm run build

############################################################
# Runtime image
############################################################
FROM base AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    RUN_MIGRATIONS=true \
    SEED_ON_FIRST_DEPLOY=false

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint
# Guard against CRLF line endings when building from a Windows checkout.
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint

COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD wget -qO- http://127.0.0.1/up > /dev/null || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
