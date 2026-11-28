#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SITE_DIR="$(dirname "$ROOT_DIR")/$(basename "$ROOT_DIR")-private"
BACKUP_DIR="$SITE_DIR/backups"
TEST_DB="customer_portal_restore_test"
TEST_USER="customer_portal_restore_test"
TMP_DIR=""
MYSQL_CNF=""

cleanup() {
  [[ -n "$TMP_DIR" && -d "$TMP_DIR" ]] && rm -rf -- "$TMP_DIR"
  [[ -n "$MYSQL_CNF" && -f "$MYSQL_CNF" ]] && rm -f -- "$MYSQL_CNF"
}
trap cleanup EXIT
fail() { echo "ERROR: $*" >&2; exit 1; }

[[ -f "$ROOT_DIR/.env" ]] || fail ".env tidak ditemukan."
command -v mysql >/dev/null 2>&1 || fail "mysql client tidak ditemukan."
command -v tar >/dev/null 2>&1 || fail "tar tidak ditemukan."

env_value() {
  local key="$1" line value
  line="$(grep -m1 -E "^[[:space:]]*${key}[[:space:]]*=" "$ROOT_DIR/.env" || true)"
  [[ -n "$line" ]] || return 1
  value="${line#*=}"
  value="$(printf '%s' "$value" | sed -E 's/^[[:space:]]*//; s/[[:space:]]*$//')"
  if [[ "$value" == \"* || "$value" == \'* ]]; then
    value="${value:1:${#value}-2}"
  else
    value="${value%%[[:space:]]#*}"
  fi
  printf '%s' "$value"
}

DB_HOST="$(env_value database.default.hostname || true)"
DB_PORT="$(env_value database.default.port || true)"
DB_PORT="${DB_PORT:-3306}"
[[ -n "$DB_HOST" ]] || fail "Host database tidak ditemukan di .env."
[[ "$TEST_DB" =~ ^[A-Za-z0-9_]+$ && "$TEST_USER" =~ ^[A-Za-z0-9_]+$ ]] || fail "Nama database/user uji tidak valid."

ARCHIVE="${1:-}"
if [[ -z "$ARCHIVE" ]]; then
  echo "Backup tersedia:"
  find "$BACKUP_DIR" -maxdepth 1 -type f -name 'customer-portal-*.tar.gz' -printf '%f\n' | sort -r
  read -r -p "Nama arsip yang akan diuji: " NAME
  ARCHIVE="$BACKUP_DIR/$NAME"
fi
[[ -f "$ARCHIVE" ]] || fail "Arsip tidak ditemukan."
case "$ARCHIVE" in "$BACKUP_DIR"/*) ;; *) fail "Arsip harus berada di folder backup privat."; esac
[[ "$(basename "$ARCHIVE")" == customer-portal-*.tar.gz ]] || fail "Nama arsip tidak valid."

TMP_DIR="$(mktemp -d)"
tar -tzf "$ARCHIVE" >/dev/null || fail "Arsip rusak."
tar -xzf "$ARCHIVE" -C "$TMP_DIR"
[[ -s "$TMP_DIR/database.sql" ]] || fail "database.sql tidak ada atau kosong."
[[ -f "$TMP_DIR/.env" ]] || fail "File .env tidak ada di arsip."

echo "Database tujuan: $TEST_DB"
echo "User MySQL uji: $TEST_USER"
read -r -s -p "Masukkan password user database uji: " TEST_PASS
echo
[[ -n "$TEST_PASS" ]] || fail "Password user database uji tidak boleh kosong."

MYSQL_CNF="$(mktemp)"
chmod 600 "$MYSQL_CNF"
escape_option() { local v="$1"; v="${v//\\/\\\\}"; v="${v//\"/\\\"}"; printf '"%s"' "$v"; }
{
  printf '[client]\n'
  printf 'host=%s\n' "$(escape_option "$DB_HOST")"
  printf 'user=%s\n' "$(escape_option "$TEST_USER")"
  printf 'password=%s\n' "$(escape_option "$TEST_PASS")"
  printf 'port=%s\n' "$(escape_option "$DB_PORT")"
} > "$MYSQL_CNF"
unset TEST_PASS

echo "Memeriksa akses ke database uji..."
DB_EXISTS="$(mysql --defaults-extra-file="$MYSQL_CNF" -N -B -e "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = '$TEST_DB'")"
[[ "$DB_EXISTS" == "1" ]] || fail "Database '$TEST_DB' tidak ditemukan atau user tidak memiliki akses."
TABLE_COUNT_BEFORE="$(mysql --defaults-extra-file="$MYSQL_CNF" -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$TEST_DB'")"
[[ "$TABLE_COUNT_BEFORE" == "0" ]] || fail "Database uji tidak kosong ($TABLE_COUNT_BEFORE tabel). Hentikan agar data yang sudah ada tidak tertimpa."

echo "Mengimpor dump ke database uji (database produksi tidak disentuh)..."
mysql --defaults-extra-file="$MYSQL_CNF" "$TEST_DB" < "$TMP_DIR/database.sql"

TABLE_COUNT="$(mysql --defaults-extra-file="$MYSQL_CNF" -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$TEST_DB'")"
[[ "$TABLE_COUNT" =~ ^[0-9]+$ && "$TABLE_COUNT" -gt 0 ]] || fail "Import selesai tetapi tidak ditemukan tabel."

echo
echo "UJI RESTORE BERHASIL"
echo "Database uji: $TEST_DB"
echo "Jumlah tabel: $TABLE_COUNT"
echo "Arsip: $(basename "$ARCHIVE")"
echo "Database produksi tidak diubah."
echo
echo "Setelah selesai memeriksa, hapus hanya database uji melalui aaPanel."
echo "Jangan menghapus atau mengubah database produksi."
