# Customer Portal CI — Pesona Wifi

Migrasi PHP native berbasis CodeIgniter 4 dari aplikasi Customer Portal Node.js Docker.

## Stack
- PHP 8.1+ dan CodeIgniter 4
- MySQL/MariaDB (data aplikasi)
- GenieACS NBI melalui HTTP API
- GOWA Pesona yang sudah berjalan, dipakai bersama Pesona Cloud Drive
- Tidak membutuhkan Node.js atau Docker untuk runtime Customer Portal

## Instalasi aaPanel / Ubuntu
1. Buat website PHP 8.1+ atau lebih baru. Aktifkan ekstensi `curl`, `fileinfo`, `intl`, `mbstring`, `mysqli`, `openssl`, `json`, dan `zip`.
2. Set document root ke folder `public`.
3. Clone repository ke direktori aplikasi.
4. Jalankan `composer install --no-dev --optimize-autoloader`.
5. Salin `.env.example` menjadi `.env`, lalu isi URL aplikasi, encryption key, dan koneksi database.
6. Jalankan `php spark migrate`.
7. Buat admin pertama dengan environment `ADMIN_USERNAME` dan `ADMIN_PASSWORD`, lalu jalankan `php spark db:seed PortalSeeder`.
8. Isi konfigurasi GenieACS dan GOWA pada `.env` server. Jangan commit kredensial produksi ke GitHub.

## Integrasi GOWA
Customer Portal memakai instance GOWA Pesona yang sama dengan Pesona Cloud Drive; tidak memasang atau menjalankan gateway baru.

Konfigurasi server:
- `gowa.baseURL=https://wa.pesonawifi.my.id`
- `gowa.deviceId=billing`
- `gowa.username` dan `gowa.password`: kredensial Basic Auth GOWA yang sudah digunakan pada server.

Pengiriman pesan:
- Method: `POST`
- Path: `/send/message`
- Authentication: HTTP Basic Auth
- Header: `X-Device-Id: billing`
- JSON body: `{"phone":"628xxxxxxxxxx@s.whatsapp.net","message":"Pesan" }`

Nomor penerima dinormalisasi menjadi WhatsApp JID. Aplikasi memeriksa HTTP status dan respons JSON GOWA; OTP hanya dinyatakan terkirim jika GOWA menerima permintaan tanpa indikasi error. Endpoint status terpisah tidak diasumsikan tersedia; status device dikonfirmasi melalui hasil pengiriman pesan.

## GenieACS
- `GENIEACS_URL` menunjuk ke GenieACS NBI (umumnya port 7557).
- Isi username/password jika NBI dilindungi autentikasi.
- Jalur parameter WiFi dapat berbeda antar model dan firmware perangkat. Verifikasi parameter perangkat sebelum mengaktifkan perubahan konfigurasi.

## Default routes
- `/` login OTP pelanggan
- `/dashboard` dashboard pelanggan
- `/admin` administrasi
- `/api/ping` health check

## Keamanan produksi
- Gunakan HTTPS.
- Gunakan encryption key acak dan password admin kuat.
- Jangan expose database atau GenieACS NBI ke internet publik.
- Simpan kredensial GOWA hanya di `.env` server, bukan di repository.
- Batasi akses admin dan gateway melalui firewall.
- Backup database dan writable uploads secara terjadwal.
