#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

APP_NAME="SOKRAT CRM V3"
REPO_URL="https://github.com/moayed33/SokratCRM-v3.git"
APP_DIR="/var/www/html/crm-v3"
DB_NAME="sokrat_crm_v3"
DB_USER="sokrat_crm_v3_app"
SITE_NAME="sokrat-crm-v3"
SITE_CONF="/etc/apache2/sites-available/${SITE_NAME}.conf"
CREDENTIALS_FILE="/root/sokrat-crm-v3-credentials.txt"

APP_CREATED=0
DB_CREATED=0
DB_USERS_CREATED=0
SITE_CREATED=0
DEFAULT_DISABLED=0

log() {
    printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

fail() {
    printf '\nERROR: %s\n' "$*" >&2
    exit 1
}

cleanup_on_error() {
    local rc=$?
    trap - ERR
    set +e

    printf '\nInstallation failed. Cleaning CRM-specific changes...\n' >&2

    if [ "$SITE_CREATED" -eq 1 ]; then
        a2dissite "$SITE_NAME" >/dev/null 2>&1 || true
        rm -f "$SITE_CONF"
    fi

    if [ "$DEFAULT_DISABLED" -eq 1 ]; then
        a2ensite 000-default >/dev/null 2>&1 || true
    fi

    apache2ctl configtest >/dev/null 2>&1 && systemctl reload apache2 >/dev/null 2>&1 || true

    if command -v mysql >/dev/null 2>&1; then
        if [ "$DB_CREATED" -eq 1 ]; then
            mysql -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" >/dev/null 2>&1 || true
        fi
        if [ "$DB_USERS_CREATED" -eq 1 ]; then
            mysql -e "DROP USER IF EXISTS '${DB_USER}'@'127.0.0.1'; DROP USER IF EXISTS '${DB_USER}'@'localhost';" >/dev/null 2>&1 || true
        fi
    fi

    if [ "$APP_CREATED" -eq 1 ] && [ "${APP_DIR_IS_CURRENT:-0}" -eq 0 ]; then
        rm -rf "$APP_DIR"
    fi

    printf 'CRM-specific rollback complete. System packages installed by apt were left in place.\n' >&2
    exit "$rc"
}

trap cleanup_on_error ERR

[ "${EUID}" -eq 0 ] || fail "Run this installer as root or with sudo."

[ -r /etc/os-release ] || fail "Cannot detect operating system."
. /etc/os-release

[ "${ID:-}" = "ubuntu" ] || fail "This installer supports Ubuntu (24.04 or 22.04 LTS). Detected OS: ${ID:-unknown}."
if [ "${VERSION_ID:-}" != "24.04" ] && [ "${VERSION_ID:-}" != "22.04" ]; then
    fail "This installer supports Ubuntu 24.04 LTS or 22.04 LTS. Detected: ${VERSION_ID:-unknown}."
fi

SERVER_HOST="${SERVER_HOST:-localhost}"
VOIP_HOST="${VOIP_HOST:-192.168.100.128}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR_IS_CURRENT=0

if [ "$SCRIPT_DIR" = "$APP_DIR" ]; then
    APP_DIR_IS_CURRENT=1
elif [ -d "$APP_DIR" ] && [ "$(ls -A "$APP_DIR" 2>/dev/null)" ]; then
    fail "$APP_DIR already exists and is not empty. Please run uninstall.sh or clear $APP_DIR before running a fresh installation."
fi

if [ -f "$SITE_CONF" ]; then
    fail "$SITE_CONF already exists. Fresh installation only."
fi

log "Checking system memory and swap"
TOTAL_RAM_KB=$(awk '/MemTotal/{print $2}' /proc/meminfo 2>/dev/null || echo "4000000")
TOTAL_SWAP_KB=$(awk '/SwapTotal/{print $2}' /proc/meminfo 2>/dev/null || echo "0")
if [ "$TOTAL_RAM_KB" -lt 2500000 ] && [ "$TOTAL_SWAP_KB" -lt 1000000 ]; then
    log "Low memory detected (${TOTAL_RAM_KB} kB RAM, ${TOTAL_SWAP_KB} kB swap)"
    if [ ! -f /swapfile ]; then
        log "Creating 2GB swapfile to prevent Out-Of-Memory during composer/npm builds..."
        fallocate -l 2G /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count=2048
        chmod 600 /swapfile
        mkswap /swapfile >/dev/null 2>&1
        swapon /swapfile >/dev/null 2>&1 || true
        if ! grep -q '/swapfile' /etc/fstab 2>/dev/null; then
            echo '/swapfile none swap sw 0 0' >> /etc/fstab || true
        fi
        log "Swapfile enabled successfully."
    fi
fi

if [ "${VERSION_ID:-}" = "22.04" ]; then
    log "Configuring ondrej/php PPA for PHP 8.3 on Ubuntu 22.04"
    export DEBIAN_FRONTEND=noninteractive
    apt-get update
    apt-get install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
fi

log "Installing Apache, MySQL, PHP 8.3, Composer, and system packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y \
    apache2 \
    mysql-server \
    git \
    curl \
    ca-certificates \
    gnupg \
    unzip \
    openssl \
    composer \
    libapache2-mod-php8.3 \
    php8.3-cli \
    php8.3-common \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-gd \
    php8.3-sqlite3 \
    php8.3-gmp

log "Ensuring Node.js 20.x LTS is installed (required for Vite 8 / Rolldown)"
NODE_MAJOR="$(node -v 2>/dev/null | cut -d'.' -f1 | tr -d 'v' || echo "0")"
if [ -z "$NODE_MAJOR" ] || [ "$NODE_MAJOR" -lt 20 ]; then
    log "Configuring NodeSource repository for Node.js 20.x"
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi

NODE_VER="$(node -v)"
log "Detected Node.js version: ${NODE_VER}"

systemctl enable --now mysql apache2

PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
[ "$PHP_VERSION" = "8.3" ] || fail "PHP 8.3 is required. Detected: $PHP_VERSION"

log "Checking database isolation names"
if mysql -NBe "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}'" | grep -Fxq "$DB_NAME"; then
    fail "Database ${DB_NAME} already exists. Fresh installation only or run uninstall.sh first."
fi

if mysql -NBe "SELECT User FROM mysql.user WHERE User='${DB_USER}' LIMIT 1" | grep -Fxq "$DB_USER"; then
    fail "MySQL user ${DB_USER} already exists. Fresh installation only or run uninstall.sh first."
fi

log "Preparing application files in ${APP_DIR}"
if [ "$APP_DIR_IS_CURRENT" -eq 1 ]; then
    log "Using current directory as APP_DIR (${APP_DIR})"
else
    mkdir -p "$APP_DIR"
    if [ -d "$SCRIPT_DIR/.git" ] || [ -f "$SCRIPT_DIR/composer.json" ]; then
        log "Copying repository contents from ${SCRIPT_DIR} to ${APP_DIR}"
        cp -a "$SCRIPT_DIR/." "$APP_DIR/"
    else
        log "Cloning repository from ${REPO_URL} into ${APP_DIR}"
        git clone --depth 1 "$REPO_URL" "$APP_DIR"
    fi
    APP_CREATED=1
fi

cd "$APP_DIR"

log "Installing PHP dependencies via Composer"
COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

if [ -f package.json ]; then
    log "Installing JavaScript dependencies and building Vite assets"
    npm install --no-audit --no-fund --no-progress
    npm run build
fi

DB_PASSWORD="$(openssl rand -hex 24)"
CRM_ADMIN_USER="admin"
CRM_ADMIN_PASSWORD='Admin@123'
CRM_ADMIN_NAME="مدير النظام"
CRM_ADMIN_EMAIL="admin@localhost.invalid"

log "Creating isolated CRM database and database user"
mysql -e "CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
DB_CREATED=1
mysql -e "CREATE USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}'; CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
DB_USERS_CREATED=1
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1'; GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

log "Creating production environment configuration"
cp .env.example .env

set_env() {
    local key="$1"
    local value="$2"
    value="${value%\"}"
    value="${value#\"}"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=\"${value}\"|" .env
    else
        echo "${key}=\"${value}\"" >> .env
    fi
}

set_env APP_NAME "$APP_NAME"
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "http://${SERVER_HOST}"
set_env VOIP_API_URL "http://${VOIP_HOST}:8090/api/integrations/crm/v1"
set_env DB_CONNECTION mysql
set_env DB_HOST 127.0.0.1
set_env DB_PORT 3306
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "$DB_PASSWORD"
set_env CRM_V2_ADMIN_USER "$CRM_ADMIN_USER"
set_env CRM_V2_ADMIN_PASSWORD "$CRM_ADMIN_PASSWORD"
set_env CRM_V2_ADMIN_NAME "$CRM_ADMIN_NAME"
set_env CRM_V2_ADMIN_EMAIL "$CRM_ADMIN_EMAIL"

php artisan key:generate --force --no-interaction
php artisan config:clear --no-ansi

log "Running CRM database migrations"
php artisan migrate --force --no-interaction

log "Seeding CRM pipeline and default Super Admin"
php artisan db:seed --force --no-interaction

php artisan storage:link --no-interaction >/dev/null 2>&1 || true

log "Caching views and routes"
mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs storage/app/private storage/app/public
php artisan view:clear --no-ansi || true
php artisan view:cache --no-ansi || true
php artisan route:cache --no-ansi || true
php artisan config:cache --no-ansi || true

log "Setting file permissions for www-data"
chmod 755 /var/www /var/www/html 2>/dev/null || true
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 775 {} +
find "$APP_DIR" -type f -exec chmod 664 {} +
chmod +x "${APP_DIR}/artisan"

log "Configuring Apache VirtualHost for HTTP (port 80) and HTTPS (port 443)"
mkdir -p /etc/ssl/certs /etc/ssl/private
if [ ! -f /etc/ssl/certs/crm-selfsigned.crt ] || [ ! -f /etc/ssl/private/crm-selfsigned.key ]; then
    log "Generating self-signed SSL certificate for HTTPS (port 443)"
    openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
        -keyout /etc/ssl/private/crm-selfsigned.key \
        -out /etc/ssl/certs/crm-selfsigned.crt \
        -subj "/CN=localhost/O=SokratCRM" >/dev/null 2>&1
fi

cat > "$SITE_CONF" <<APACHE
<VirtualHost *:80>
    ServerName ${SERVER_HOST}
    ServerAlias localhost 127.0.0.1
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Sokrat Voice WebRTC Reverse Proxy
    ProxyPreserveHost Off
    ProxyPass /phone/ http://${VOIP_HOST}:8090/
    ProxyPassReverse /phone/ http://${VOIP_HOST}:8090/

    # Asterisk WebSocket Reverse Proxy
    RewriteEngine On
    RewriteRule ^/phone$ /phone/ [R=301,L]
    RewriteCond %{HTTP:Upgrade} =websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/ws$ ws://${VOIP_HOST}:8088/ws [P,L]

    ErrorLog \${APACHE_LOG_DIR}/${SITE_NAME}-error.log
    CustomLog \${APACHE_LOG_DIR}/${SITE_NAME}-access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName ${SERVER_HOST}
    ServerAlias localhost 127.0.0.1
    DocumentRoot ${APP_DIR}/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/crm-selfsigned.crt
    SSLCertificateKeyFile /etc/ssl/private/crm-selfsigned.key

    <Directory ${APP_DIR}/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Sokrat Voice WebRTC Reverse Proxy
    ProxyPreserveHost Off
    ProxyPass /phone/ http://${VOIP_HOST}:8090/
    ProxyPassReverse /phone/ http://${VOIP_HOST}:8090/

    # Asterisk WebSocket Reverse Proxy (WSS -> WS)
    RewriteEngine On
    RewriteRule ^/phone$ /phone/ [R=301,L]
    RewriteCond %{HTTP:Upgrade} =websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/ws$ ws://${VOIP_HOST}:8088/ws [P,L]

    ErrorLog \${APACHE_LOG_DIR}/${SITE_NAME}-ssl-error.log
    CustomLog \${APACHE_LOG_DIR}/${SITE_NAME}-ssl-access.log combined
</VirtualHost>
APACHE
SITE_CREATED=1

a2enmod rewrite ssl proxy proxy_http proxy_wstunnel headers >/dev/null
if a2query -s 000-default >/dev/null 2>&1; then
    a2dissite 000-default >/dev/null
    DEFAULT_DISABLED=1
fi
a2ensite "$SITE_NAME" >/dev/null
apache2ctl configtest
systemctl reload apache2

log "Running application health checks"
php artisan migrate:status --no-ansi >/dev/null
curl -fsS --max-time 15 "http://localhost/login" >/dev/null || curl -fsS --max-time 15 "http://127.0.0.1/login" >/dev/null

cat > "$CREDENTIALS_FILE" <<CREDS
${APP_NAME} INSTALLATION CREDENTIALS
======================================
URL=http://localhost/
Admin user=${CRM_ADMIN_USER}
Admin password=${CRM_ADMIN_PASSWORD}
Database=${DB_NAME}
Database user=${DB_USER}
Database password=${DB_PASSWORD}
Installed at=$(date '+%Y-%m-%d %H:%M:%S')
Installed from=${REPO_URL}
CREDS
chmod 600 "$CREDENTIALS_FILE"

trap - ERR

printf '\n==================================================\n'
printf '%s INSTALLATION COMPLETE\n' "$APP_NAME"
printf '==================================================\n'
printf 'URL: http://localhost/\n'
printf 'Admin user: %s\n' "$CRM_ADMIN_USER"
printf 'Admin password: %s\n' "$CRM_ADMIN_PASSWORD"
printf 'Credentials saved to: %s\n' "$CREDENTIALS_FILE"
printf 'Database: %s\n' "$DB_NAME"
printf 'Database user: %s\n' "$DB_USER"
printf '==================================================\n'
