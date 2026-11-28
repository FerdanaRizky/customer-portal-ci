# Customer Portal Backup & Restore

## Lokasi backup

Skrip menyimpan arsip di luar document root:

`../portal.pesonawifi.my.id-private/backups/` (nama folder mengikuti nama folder aplikasi).

Folder dibuat dengan permission 700 dan arsip dengan permission 600.

## Isi backup

- Dump MySQL/MariaDB (`database.sql`), termasuk routines, triggers, dan events.
- File `.env` yang berisi konfigurasi dan kunci enkripsi.
- `public/uploads/` (termasuk logo yang diunggah).
- `public/.user.ini`, jika tersedia.

Arsip berisi rahasia aplikasi karena menyertakan `.env`. Jangan pindahkan ke document root, jangan unggah ke GitHub, dan batasi akses SSH ke server. Simpan salinan di media aman lain jika diperlukan untuk pemulihan saat server hilang.

## Membuat backup

Jalankan dari direktori aplikasi:

```bash
cd /www/wwwroot/portal.pesonawifi.my.id
bash scripts/backup.sh
```

Skrip membaca konfigurasi database dari `.env`, membuat dump, lalu menyimpan tujuh arsip backup terbaru. Arsip lama yang melampaui tujuh file akan dihapus.

## Memeriksa backup

```bash
ls -lh /www/wwwroot/portal.pesonawifi.my.id-private/backups/
tar -tzf /www/wwwroot/portal.pesonawifi.my.id-private/backups/customer-portal-NAMA-ARSIP.tar.gz
```

Ganti `NAMA-ARSIP` dengan nama file yang benar.

## Restore

Restore bersifat destruktif: isi database aktif akan diganti dengan isi backup. Skrip meminta konfirmasi dengan mengetik `RESTORE` dan membuat dump pengaman database aktif sebelum import.

```bash
cd /www/wwwroot/portal.pesonawifi.my.id
bash scripts/restore.sh
```

Atau berikan nama file backup sebagai argumen:

```bash
bash scripts/restore.sh customer-portal-YYYYMMDD-HHMMSS.tar.gz
```

Skrip restore menggunakan koneksi database dari `.env` yang sedang aktif dan tidak menimpa `.env` aktif. Untuk pemulihan server baru, pulihkan `.env` dari arsip secara manual ke direktori aplikasi, atur permission yang ketat, pastikan kunci `encryption.key` sama dengan saat backup dibuat, lalu jalankan restore.

## Persyaratan

- Bash
- MySQL/MariaDB client: `mysql` dan `mysqldump`
- `tar`, `gzip`, `grep`, `sed`, `find`, `awk`

Jalankan uji restore hanya pada waktu pemeliharaan atau database uji. Jangan menguji restore pada database produksi karena seluruh data database aktif akan diganti.


## Uji restore tanpa menyentuh database produksi

Gunakan skrip terpisah ini untuk menguji apakah dump dapat diimpor ke database baru. Skrip membuat database bernama `<database_aktif>_restore_test_<timestamp>`, lalu mengimpor dump ke sana. Database produksi tidak dihapus atau ditimpa.

```bash
cd /www/wwwroot/portal.pesonawifi.my.id
bash scripts/test-restore.sh
```

Pilih arsip backup saat diminta. Akun database yang dipakai harus memiliki hak `CREATE DATABASE`. Jika hak tersebut tidak tersedia, skrip berhenti pada pembuatan database uji dan database produksi tetap tidak disentuh.

Setelah pengujian, periksa tabel pada database uji. Jika sudah selesai, hapus hanya database uji yang namanya dicetak oleh skrip:

```sql
DROP DATABASE `NAMA_DATABASE_UJI`;
```

Jangan menjalankan perintah DROP terhadap nama database produksi.
