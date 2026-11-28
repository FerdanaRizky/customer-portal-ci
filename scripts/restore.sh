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
[[ -f "$ROOT_DIR/.env" ]] || fail ".env aktif tidak ditemukan. Restore menggunakan koneksi database dari .env aktif."
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

ARCHIVE="${1:-}"
if [[ -z "$ARCHIVE" ]]; then
  echo "Backup tersedia:"
  find "$BACKUP_DIR" -maxdepth 1 -type f -name 'customer-portal-*.tar.gz' -printf '%f\n' | sort -r
  read -r -p "Nama file backup yang akan dipulihkan: " NAME
  ARCHIVE="$BACKUP_DIR/$NAME"
fi
[[ -f "$ARCHIVE" ]] || fail "File backup tidak ditemukan."
case "$ARCHIVE" in "$BACKUP_DIR"/*) ;; *) fail "Backup harus berada di folder backup privat: $BACKUP_DIR";; esac
[[ "$(basename "$ARCHIVE")" == customer-portal-*.tar.gz ]] || fail "Nama arsip backup tidak valid."

echo "PERINGATAN: Restore akan MENGGANTI seluruh isi database '$DB_NAME'."
echo "File public/uploads juga akan diganti jika tersedia dalam arsip."
echo "File .env aktif TIDAK akan ditimpa oleh proses restore ini."
read -r -p "Ketik RESTORE untuk melanjutkan: " CONFIRM
[[ "$CONFIRM" == "RESTORE" ]] || fail "Restore dibatalkan."

TMP_DIR="$(mktemp -d)"
tar -tzf "$ARCHIVE" >/dev/null || fail "Arsip backup rusak atau tidak valid."
tar -xzf "$ARCHIVE" -C "$TMP_DIR"
[[ -s "$TMP_DIR/database.sql" ]] || fail "database.sql tidak ditemukan atau kosong dalam arsip."

MYSQL_CNF="$(mktemp)"
chmod 600 "$MYSQL_CNF"
escape_option() { local v="$1"; v="${v//\\/\\\\}"; v="${v//\"/\\\"}"; printf '"%s"' "$v"; }
{
  printf '[client]\n'
  printf 'host=%s\n' "$(escape_option "$DB_HOST")"
  printf 'user=%s\n' "$(escape_option "$DB_USER")"
  printf 'password=%s\n' "$(escape_option "$DB_PASS")"
  printf 'port=%s\n' "$(escape_option "$DB_PORT")"
} > "$MYSQL_CNF"

# Save a safety copy before destructive import.
SAFETY="$BACKUP_DIR/pre-restore-$(date +%Y%m%d-%H%M%S).sql"
mysqldump --defaults-extra-file="$MYSQL_CNF" --single-transaction \
  --routines --triggers --events --hex-blob "$DB_NAME" > "$SAFETY"
chmod 600 "$SAFETY"
[[ -s "$SAFETY" ]] || fail "Backup pengaman sebelum restore gagal. Restore dibatalkan."

echo "Memulihkan database..."
mysql --defaults-extra-file="$MYSQL_CNF" "$DB_NAME" < "$TMP_DIR/database.sql"

if [[ -d "$TMP_DIR/public/uploads" ]]; then
  LIVE_UPLOADS="$ROOT_DIR/public/uploads"
  OLD_UPLOADS="$BACKUP_DIR/uploads-pre-restore-$(date +%Y%m%d-%H%M%S)"
  [[ ! -e "$LIVE_UPLOADS" ]] || mv "$LIVE_UPLOADS" "$OLD_UPLOADS"
  mkdir -p "$(dirname "$LIVE_UPLOADS")"
  cp -a "$TMP_DIR/public/uploads" "$LIVE_UPLOADS"
fi

echo "Restore database dan uploads selesai."
echo "Backup pengaman database sebelum restore: $SAFETY"
echo "Periksa login admin, login pelanggan, logo, dan pengaturan aplikasi."
