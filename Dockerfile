# syntax=docker/dockerfile:1

# ---------------------------------------------------------------
# PHP runtime: FrankenPHP (Caddy + PHP) với các extension Laravel/Filament cần
# ---------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4 AS base

RUN install-php-extensions pdo_mysql pdo_pgsql intl zip gd bcmath pcntl opcache exif

# Gỡ file capability: host chạy container với quyền hạn chế (Render) từ chối exec binary có capability
RUN setcap -r /usr/local/bin/frankenphp

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini

ENV SERVER_NAME=:80 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# ---------------------------------------------------------------
# Thư viện PHP (tách layer để cache khi chỉ sửa code)
# ---------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# ---------------------------------------------------------------
# Build CSS/JS bằng Vite
# ---------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------------------------------------------------------------
# Image chạy ứng dụng (web, queue, scheduler dùng chung)
# ---------------------------------------------------------------
FROM base AS app

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

# Tạo thư mục storage trước: script của composer (view:clear...) cần chúng tồn tại
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --no-interaction

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

EXPOSE 80

HEALTHCHECK --interval=10s --timeout=5s --start-period=60s --retries=5 \
    CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["app-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
