# syntax=docker/dockerfile:1

# ---------------------------------------------------------------
# PHP runtime: FrankenPHP (Caddy + PHP)
# ---------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4 AS base

# Chỉ cài extension app thực sự dùng (biên dịch extension là bước chậm nhất của build):
# pdo_pgsql (DB), intl (Filament), zip (xuất file của Filament/openspout), pcntl (queue worker), opcache.
# Gỡ file capability trong cùng layer: host chạy container với quyền hạn chế (Render) từ chối exec binary có capability.
RUN install-php-extensions pdo_pgsql intl zip pcntl opcache \
    && setcap -r /usr/local/bin/frankenphp

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

# Log theo ngày (giữ 15 ngày) và ra stderr để vẫn xem được ở mục Logs của Render; biến môi trường trên Render ghi đè được
ENV SERVER_NAME=:80 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    LOG_CHANNEL=stack \
    LOG_STACK=daily,stderr \
    LOG_DAILY_DAYS=15

WORKDIR /app

# ---------------------------------------------------------------
# Thư viện PHP: chỉ chạy lại khi composer.json/composer.lock đổi
# ---------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# ---------------------------------------------------------------
# Build CSS/JS bằng Vite
# ---------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund --prefer-offline
COPY . .
# Tailwind chỉ quét thêm view phân trang của Laravel trong vendor (@source trong app.css), không cần chép cả thư mục vendor
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---------------------------------------------------------------
# Image chạy ứng dụng (web, queue, scheduler dùng chung)
# ---------------------------------------------------------------
FROM base AS app

COPY --link --from=vendor /app/vendor ./vendor
COPY . .
COPY --link --from=assets /app/public/build ./public/build

# Tạo thư mục storage trước: script của composer (view:clear...) cần chúng tồn tại
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --no-interaction

COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint

EXPOSE 80

HEALTHCHECK --interval=10s --timeout=5s --start-period=60s --retries=5 \
    CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["app-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
