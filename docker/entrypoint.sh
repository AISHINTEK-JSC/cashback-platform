#!/bin/sh
# Chuẩn bị ứng dụng trước khi chạy tiến trình chính của container.
# CONTAINER_ROLE=app      : chạy migrate (+ seed nếu bật), rồi khởi động web server
# CONTAINER_ROLE=worker   : chỉ chạy lệnh được truyền vào (queue, scheduler)
set -e

cd /app

# APP_KEY dùng chung cho mọi container qua volume storage, tự sinh ở lần chạy đầu.
if [ -z "$APP_KEY" ]; then
    KEY_FILE=storage/app/.app_key
    if [ ! -s "$KEY_FILE" ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$KEY_FILE"
        echo "Đã tạo APP_KEY mới tại $KEY_FILE"
    fi
    APP_KEY="$(cat "$KEY_FILE")"
    export APP_KEY
fi

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

# storage là volume dùng lại qua các lần build: view compile từ image cũ có thể "mới" hơn file nguồn
# (COPY giữ nguyên mtime) nên Laravel không tự compile lại. Xoá đi để luôn dùng view của image hiện tại.
php artisan view:clear --no-interaction >/dev/null

if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    echo "Đang chờ database..."
    attempts=0
    until php artisan migrate --force --no-interaction; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 30 ]; then
            echo "Không kết nối được database sau 30 lần thử." >&2
            exit 1
        fi
        sleep 2
    done

    if [ "${SEED_DATABASE:-false}" = "true" ]; then
        php artisan db:seed --force --no-interaction
    fi

    php artisan storage:link --force >/dev/null 2>&1 || true
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan optimize

    # Đăng ký lại webhook + danh sách lệnh của bot Telegram mỗi lần deploy (không có token thì tự bỏ qua, lỗi không chặn khởi động)
    if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
        php artisan telegram:setup --no-interaction || true
    fi
fi

# Host như Render cấp cổng qua $PORT; mặc định nghe cổng 80.
if [ -n "$PORT" ]; then
    export SERVER_NAME=":$PORT"
fi

exec docker-php-entrypoint "$@"
