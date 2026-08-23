#!/usr/bin/env bash
set -Eeuo pipefail

APP_NAME="SOKRAT CRM V2"
APP_DIR="/var/www/html/crm-v2"
DB_NAME="sokrat_crm_v2"
DB_USER="sokrat_crm_v2_app"
SITE_NAME="sokrat-crm-v2"
SITE_CONF="/etc/apache2/sites-available/${SITE_NAME}.conf"
CREDENTIALS_FILE="/root/sokrat-crm-v2-credentials.txt"

log() {
    printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

fail() {
    printf '\nERROR: %s\n' "$*" >&2
    exit 1
}

[ "${EUID}" -eq 0 ] || fail "Run this uninstaller as root or with sudo."

FORCE=0
for arg in "$@"; do
    case "$arg" in
        -y|--force)
            FORCE=1
            ;;
    esac
done

if [ "$FORCE" -ne 1 ]; then
    printf '==================================================\n'
    printf 'WARNING: Uninstalling %s\n' "$APP_NAME"
    printf 'This will permanently remove:\n'
    printf '  - Apache site config: %s\n' "$SITE_CONF"
    printf '  - Application directory: %s\n' "$APP_DIR"
    printf '  - MySQL database: %s\n' "$DB_NAME"
    printf '  - MySQL user: %s\n' "$DB_USER"
    printf '  - Credentials file: %s\n' "$CREDENTIALS_FILE"
    printf '==================================================\n'
    read -rp "Are you sure you want to continue? [y/N]: " confirm
    case "$confirm" in
        [yY][eE][sS]|[yY])
            log "Confirmed uninstallation. Starting purge..."
            ;;
        *)
            log "Uninstallation canceled."
            exit 0
            ;;
    esac
fi

log "Disabling and removing Apache site configuration"
if command -v a2dissite >/dev/null 2>&1; then
    a2dissite "$SITE_NAME" >/dev/null 2>&1 || true
fi

if [ -f "$SITE_CONF" ]; then
    rm -f "$SITE_CONF"
    log "Removed ${SITE_CONF}"
fi

if command -v a2ensite >/dev/null 2>&1 && [ -f /etc/apache2/sites-available/000-default.conf ]; then
    a2ensite 000-default >/dev/null 2>&1 || true
fi

if command -v apache2ctl >/dev/null 2>&1 && command -v systemctl >/dev/null 2>&1; then
    apache2ctl configtest >/dev/null 2>&1 && systemctl reload apache2 >/dev/null 2>&1 || true
fi

log "Removing MySQL database and user"
if command -v mysql >/dev/null 2>&1; then
    mysql -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" >/dev/null 2>&1 || true
    mysql -e "DROP USER IF EXISTS '${DB_USER}'@'127.0.0.1'; DROP USER IF EXISTS '${DB_USER}'@'localhost';" >/dev/null 2>&1 || true
    mysql -e "FLUSH PRIVILEGES;" >/dev/null 2>&1 || true
    log "Dropped database ${DB_NAME} and user ${DB_USER}"
fi

log "Removing application directory ${APP_DIR}"
if [ -d "$APP_DIR" ]; then
    rm -rf "$APP_DIR"
    log "Removed ${APP_DIR}"
fi

log "Removing saved credentials file"
if [ -f "$CREDENTIALS_FILE" ]; then
    rm -f "$CREDENTIALS_FILE"
    log "Removed ${CREDENTIALS_FILE}"
fi

printf '\n==================================================\n'
printf '%s UNINSTALLATION COMPLETE\n' "$APP_NAME"
printf 'All CRM files, database resources, and site configurations have been purged.\n'
printf '==================================================\n'
