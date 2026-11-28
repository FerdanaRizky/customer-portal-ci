#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SITE_DIR="$(dirname "$ROOT_DIR")/$(basename "$ROOT_DIR")-private"
BACKUP_DIR="$SITE_DIR/backups"
TMP_DIR=""
MYSQL_CNF=""

cleanup() {
  [[ -n "$TMP_DIR" && -d "$TMP_DIR" ]] && rm -rf -- "$TMP_DIR"
  [[ -n "$MYSQL_CNF" && -f "$MYSQL_CNF" ]] && rm -f -- "$MYSQL_CNF"
}
trap cleanup EXIT

fail() { echo "ERROR: $*" >&2; exit 1; }
[[ -f "$ROOT_DIR/.env" ]] || fail ".env tidak ditemukan di $ROOT_DIR"
command -v mysqldump >/dev/null 2>&1 || fail "mysqldump tidak ditemukan. Pasang paket client MySQL/MariaDB."
command -v mysql >/dev/null 2>&1 || fail "mysql client tidak ditemukan."
command -v tar >/dev/null 2>&1 || fail "tar tidak ditemukan."

env_value() {
  local key="$1" line value
  line="$(grep -m1 -E "^[[:space:]]*${key}[[:space:]]*=" "$ROOT_DIR/.env" || true)"
  [[ -n "$line" ]] || return 1
  value="${line#*=}"
  value="$(printf '%s' "$value" | sed -E 's/^[[:space:]]*//; s/[[:space:]]*$//')"
  if [[ "$value" == \"*\" || "$value" == \'*\' ]]; then
    value="${value:1:${#value}-2}"
  else
    value="${value%%[[:space:]]#*}"
  fi
  printf '%s' "$value"
}

DB_HOST="$(env_value database.default.hostname || true)"
DB_NAME="$(env_value database.default.database || true)"
DB_USER="$(env_value database.default.username || true)"
DB_PASS="$(env_value database.default.password || true)"
DB_PORT="$(env_value database.default.port || true)"
DB_PORT="${DB_PORT:-3306}"
[[ -n "$DB_HOST" && -n "$DB_NAME" && -n "$DB_USER" ]] || fail "Konfigurasi database.default.* tidak lengkap di .env."
[[ "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]] || fail "Nama database mengandung karakter yang tidak didukung."

mkdir -p "$BACKUP_DIR"
chmod 700 "$SITE_DIR" "$BACKUP_DIR"
TMP_DIR="$(mktemp -d "$BACKUP_DIR/.backup-tmp.XXXXXX")"
MYSQL_CNF="$(mktemp "$BACKUP_DIR/.mysql-client.XXXXXX")"
chmod 600 "$MYSQL_CNF"

# Escape values for a MySQL option file; secrets never appear in process arguments.
escape_option() { local v="$1"; v="${v//\\/\\\\}"; v="${v//\"/\\\"}"; printf '"%s"' "$v"; }
{
  printf '[client]\n'
  printf 'host=%s\n' "$(escape_option "$DB_HOST")"
  printf 'user=%s\n' "$(escape_option "$DB_USER")"
  printf 'password=%s\n' "$(escape_option "$DB_PASS")"
  printf 'port=%s\n' "$(escape_option "$DB_PORT")"
} > "$MYSQL_CNF"

STAMP="$(date +%Y%m%d-%H%M%S)"
SQL_FILE="$TMP_DIR/database.sql"
ARCHIVE="$BACKUP_DIR/customer-portal-$STAMP.tar.gz"

echo "Membuat dump database..."
mysqldump --defaults-extra-file="$MYSQL_CNF" \
  --single-transaction --routines --triggers --events --hex-blob \
  --default-character-set=utf8mb4 "$DB_NAME" > "$SQL_FILE"

[[ -s "$SQL_FILE" ]] || fail "Dump database kosong. Backup dibatalkan."
cp -p "$ROOT_DIR/.env" "$TMP_DIR/.env"
if [[ -d "$ROOT_DIR/public/uploads" ]]; then
  mkdir -p "$TMP_DIR/public"
  cp -a "$ROOT_DIR/public/uploads" "$TMP_DIR/public/"
fi
if [[ -f "$ROOT_DIR/public/.user.ini" ]]; then
  mkdir -p "$TMP_DIR/public"
  cp -p "$ROOT_DIR/public/.user.ini" "$TMP_DIR/public/.user.ini"
fi

cat > "$TMP_DIR/backup-info.txt" <<INFO
Customer Portal backup
Created: $(date -Is)
Database: $DB_NAME
Site: $ROOT_DIR
Contents: database.sql, .env, public/uploads, public/.user.ini (if present)
INFO

tar -czf "$ARCHIVE" -C "$TMP_DIR" database.sql .env backup-info.txt \
  $( [[ -d "$TMP_DIR/public" ]] && printf '%s' public || true )
chmod 600 "$ARCHIVE"

# Keep the seven newest completed archives.
find "$BACKUP_DIR" -maxdepth 1 -type f -name 'customer-portal-*.tar.gz' \
  -printf '%T@ %p\n' | sort -nr | awk 'NR>7 {sub(/^[^ ]+ /,""); print}' | \
  while IFS= read -r old; do rm -f -- "$old"; done

echo "Backup berhasil: $ARCHIVE"
echo "Ukuran: $(du -h "$ARCHIVE" | awk '{print $1}')"
echo "Retensi: 7 backup terbaru."
echo "PERINGATAN: Arsip berisi .env dan rahasia aplikasi. Jangan unggah ke GitHub atau folder publik."
