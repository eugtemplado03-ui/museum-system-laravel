#!/bin/sh
set -eu

export APP_KEY="$(php -r '
$key = getenv("APP_KEY") ?: "";
if (strncmp($key, "base64:", 7) === 0) {
    $decoded = base64_decode(substr($key, 7), true);
    if ($decoded !== false && strlen($decoded) === 32) {
        exit(0);
    }
} elseif (strlen($key) === 32) {
    exit(0);
}
if ($key === "") {
    fwrite(STDERR, "APP_KEY must be configured.\n");
    exit(1);
}
echo "base64:" . base64_encode(hash("sha256", $key, true));
')"

export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost}}"

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

php artisan migrate --force
php artisan museum:seed-if-empty --force

exec apache2-foreground
