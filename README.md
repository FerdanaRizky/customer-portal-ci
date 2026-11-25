# Customer Portal CI — Pesona Wifi

Migrasi PHP native berbasis CodeIgniter 4 dari aplikasi Customer Portal Node.js Docker.

## Stack
- PHP 8.1+ dan CodeIgniter 4
- MySQL/MariaDB (data aplikasi)
- GenieACS NBI melalui HTTP API
- WhatsApp gateway HTTP eksternal
- Tidak membutuhkan Node.js atau Docker untuk runtime aplikasi

## Instalasi aaPanel / Ubuntu
1. Buat website PHP 8.1+ atau lebih baru. Aktifkan ekstensi `curl`, `fileinfo`, `intl`, `mbstring`, `mysqli`, `openssl`, `json`, `zip`.
2. Set document root ke folder `public`.
3. Upload/clone repository ke direktori aplikasi.
4. Jalankan `composer install --no-dev --optimize-autoloader`.
5. Salin `.env.example` menjadi `.env`, isi URL aplikasi, encryption key, dan koneksi database.
6. Jalankan `php spark migrate`.
7. Buat admin pertama dengan environment `ADMIN_USERNAME` dan `ADMIN_PASSWORD`, lalu jalankan `php spark db:seed PortalSeeder`.
8. Atur `GENIEACS_URL`, `GENIEACS_USERNAME`, `GENIEACS_PASSWORD`, `WHATSAPP_GATEWAY_URL`, dan `WHATSAPP_GATEWAY_TOKEN` melalui environment PHP-FPM / .env khusus server.

## Catatan migrasi gateway
WhatsApp pada sumber menggunakan Baileys (Node.js). Baileys tidak dapat dijalankan di PHP native; edisi ini memakai kontrak HTTP gateway eksternal:
- `POST {WHATSAPP_GATEWAY_URL}/send-message` body JSON `{phone,message}`
- `GET {WHATSAPP_GATEWAY_URL}/status`
Header otentikasi opsional: `Authorization: Bearer {WHATSAPP_GATEWAY_TOKEN}`.

Perilaku model GenieACS dan jalur parameter WiFi dapat berbeda antar vendor/firmware. Verifikasi parameter pada perangkat sebelum mengaktifkan fitur perubahan konfigurasi atau reboot untuk pelanggan.

## Default routes
- `/` login OTP pelanggan
- `/dashboard` dashboard pelanggan
- `/admin` administrasi
- `/api/ping` health check

## Keamanan produksi
- Gunakan HTTPS.
- Gunakan secret encryption acak dan password admin kuat.
- Jangan expose database/GenieACS NBI ke internet publik.
- Batasi akses admin dan gateway melalui firewall.
- Backup database dan writable uploads secara terjadwal.
