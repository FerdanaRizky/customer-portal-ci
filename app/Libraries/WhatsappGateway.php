<?php

namespace App\Libraries;

/**
 * Client untuk GOWA yang sudah berjalan.
 * Tidak memasang atau menghapus device GOWA.
 */
class WhatsappGateway
{
    private function envValue(string $key, string $default = ''): string
    {
        $value = env($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    private function config(): array
    {
        $store = new ConfigStore();

        $envUsername = $this->envValue('gowa.username');
        $envPassword = $this->envValue('gowa.password');

        return [
            'baseURL' => rtrim(
                $store->get(
                    'gowa_base_url',
                    $this->envValue('gowa.baseURL')
                ) ?? '',
                '/'
            ),

            'username' => $store->getSecret(
                'gowa_username',
                $envUsername !== '' ? $envUsername : null
            ),

            'password' => $store->getSecret(
                'gowa_password',
                $envPassword !== '' ? $envPassword : null
            ),

            'deviceId' => $store->get(
                'gowa_device_id',
                $this->envValue('gowa.deviceId', 'billing')
            ),
        ];
    }

    public function sendText(string $phone, string $message): array
    {
        $config = $this->config();

        if (
            $config['baseURL'] === '' ||
            empty($config['username']) ||
            empty($config['password']) ||
            empty($config['deviceId'])
        ) {
            throw new \RuntimeException(
                'Konfigurasi WhatsApp GOWA belum lengkap. Periksa halaman Admin.'
            );
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        if ($digits === '') {
            throw new \InvalidArgumentException(
                'Nomor WhatsApp penerima tidak valid.'
            );
        }

        $recipient = str_ends_with($phone, '@s.whatsapp.net')
            ? $phone
            : $digits . '@s.whatsapp.net';

        $payload = json_encode([
            'phone' => $recipient,
            'message' => $message,
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init($config['baseURL'] . '/send/message');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERPWD => $config['username'] . ':' . $config['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'X-Device-Id: ' . $config['deviceId'],
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($body === false) {
            log_message('error', 'GOWA cURL error: {error}', [
                'error' => $error,
            ]);

            throw new \RuntimeException(
                'Tidak dapat menghubungi GOWA: ' . $error
            );
        }

        $decoded = json_decode((string) $body, true);

        $hasError = is_array($decoded) && (
            isset($decoded['error']) ||
            isset($decoded['errors']) ||
            (
                isset($decoded['code']) &&
                strtoupper((string) $decoded['code']) !== 'SUCCESS'
            )
        );

        if ($status < 200 || $status >= 300 || $hasError) {
            log_message('error', 'GOWA send failed. HTTP {status}; response {body}', [
                'status' => $status,
                'body' => substr((string) $body, 0, 700),
            ]);

            throw new \RuntimeException(
                'GOWA gagal mengirim pesan (HTTP ' . $status .
                '). Periksa koneksi device dan log GOWA.'
            );
        }

        log_message('info', 'GOWA accepted message using device {device}.', [
            'device' => $config['deviceId'],
        ]);

        return is_array($decoded)
            ? $decoded
            : ['raw' => (string) $body];
    }


    /**
     * Membaca daftar device GOWA secara read-only.
     * Tidak mengubah sesi, logout, atau menghapus device.
     */
    public function listDevices(): array
    {
        $config = $this->config();
        if ($config['baseURL'] === '' || empty($config['username']) || empty($config['password'])) {
            throw new \RuntimeException('Konfigurasi GOWA belum lengkap.');
        }

        $ch = curl_init($config['baseURL'] . '/devices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERPWD => $config['username'] . ':' . $config['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-Device-Id: ' . $config['deviceId'],
            ],
        ]);

        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $http < 200 || $http >= 300) {
            log_message('error', 'GOWA device list failed. HTTP {status}; {error}', [
                'status' => $http,
                'error' => $error,
            ]);
            throw new \RuntimeException('Tidak dapat membaca daftar device GOWA (HTTP ' . $http . ').');
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data) || (isset($data['code']) && strtoupper((string) $data['code']) !== 'SUCCESS')) {
            throw new \RuntimeException('Respons daftar device GOWA tidak valid.');
        }

        $items = $data['results'] ?? [];
        if (!is_array($items)) {
            throw new \RuntimeException('Format daftar device GOWA tidak valid.');
        }

        $devices = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }
            $devices[] = [
                'id' => (string) $item['id'],
                'displayName' => (string) ($item['display_name'] ?? $item['id']),
                'state' => (string) ($item['state'] ?? 'unknown'),
                'jid' => (string) ($item['jid'] ?? ''),
                'createdAt' => $item['created_at'] ?? null,
            ];
        }

        return $devices;
    }

    /**
     * Membuat slot device baru khusus milik Customer Portal.
     * ID billing dan device non-Portal tidak pernah disentuh.
     */
    public function createPortalDevice(string $deviceId): array
    {
        if (!preg_match('/^portal-[a-f0-9]{16}$/', $deviceId)) {
            throw new \InvalidArgumentException('ID device Portal tidak valid.');
        }

        $config = $this->config();
        if ($config['baseURL'] === '' || empty($config['username']) || empty($config['password'])) {
            throw new \RuntimeException('Konfigurasi GOWA belum lengkap.');
        }

        foreach ($this->listDevices() as $device) {
            if (hash_equals($device['id'], $deviceId)) {
                throw new \RuntimeException('ID device sudah terdaftar. Silakan coba lagi.');
            }
        }

        $ch = curl_init($config['baseURL'] . '/devices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERPWD => $config['username'] . ':' . $config['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Device-Id: ' . $config['deviceId'],
            ],
            CURLOPT_POSTFIELDS => json_encode(['device_id' => $deviceId], JSON_THROW_ON_ERROR),
        ]);

        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $http < 200 || $http >= 300) {
            log_message('error', 'GOWA create device failed. HTTP {status}; {error}', [
                'status' => $http,
                'error' => $error,
            ]);
            throw new \RuntimeException('GOWA gagal membuat slot device (HTTP ' . $http . ').');
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data) || (isset($data['code']) && strtoupper((string) $data['code']) !== 'SUCCESS')) {
            throw new \RuntimeException('Respons pembuatan device GOWA tidak valid.');
        }

        return ['deviceId' => $deviceId];
    }

    public function status(): array
    {
        $config = $this->config();

        $configured = $config['baseURL'] !== ''
            && !empty($config['username'])
            && !empty($config['password'])
            && $config['deviceId'] !== '';

        $result = [
            'configured' => $configured,
            'connected' => false,
            'loggedIn' => false,
            'deviceId' => $config['deviceId'],
            'message' => 'Konfigurasi GOWA belum lengkap.'
        ];

        if (!$configured) {
            return $result;
        }

        $url = $config['baseURL']
            . '/devices/' . rawurlencode($config['deviceId'])
            . '/status';

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERPWD => $config['username'] . ':' . $config['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-Device-Id: ' . $config['deviceId']
            ]
        ]);

        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $http < 200 || $http >= 300) {
            log_message('error', 'GOWA status request failed. HTTP {status}; {error}', [
                'status' => $http,
                'error' => $error
            ]);

            $result['message'] = 'Tidak dapat membaca status GOWA (HTTP '
                . $http . ').';
            return $result;
        }

        $data = json_decode((string) $body, true);

        if (!is_array($data)) {
            $result['message'] = 'Respons status GOWA tidak valid.';
            return $result;
        }

        // GOWA mengirim status di dalam properti results.
        // Tetap mendukung format root untuk kompatibilitas.
        if (
            isset($data['code']) &&
            strtoupper((string) $data['code']) !== 'SUCCESS'
        ) {
            $result['message'] = 'GOWA mengembalikan status gagal.';
            return $result;
        }

        $statusData = isset($data['results']) && is_array($data['results'])
            ? $data['results']
            : $data;

        $connected = filter_var(
            $statusData['is_connected'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $loggedIn = filter_var(
            $statusData['is_logged_in'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $result['connected'] = $connected;
        $result['loggedIn'] = $loggedIn;
        $result['message'] = $loggedIn
            ? 'WhatsApp terhubung dan sudah login.'
            : ($connected
                ? 'GOWA aktif, tetapi sesi WhatsApp belum login.'
                : 'Device WhatsApp belum terhubung.');

        return $result;
    }

    /**
     * Meminta QR hanya untuk device yang dibuat khusus oleh Customer Portal.
     * Tidak pernah menjalankan login/QR untuk device billing atau device lain.
     */
    /**
     * Menghapus device khusus Customer Portal dari GOWA.
     * Device non-Portal (termasuk billing) tidak boleh dihapus melalui method ini.
     */
    public function deletePortalDevice(string $deviceId): array
    {
        if (!preg_match('/^portal-[a-f0-9]{16}$/', $deviceId)) {
            throw new \\InvalidArgumentException('Hanya device milik Customer Portal yang dapat dihapus.');
        }

        $config = $this->config();
        if ($config['baseURL'] === '' || empty($config['username']) || empty($config['password'])) {
            throw new \\RuntimeException('Konfigurasi GOWA belum lengkap.');
        }

        $url = $config['baseURL'] . '/devices/' . rawurlencode($deviceId);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERPWD => $config['username'] . ':' . $config['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-Device-Id: ' . $config['deviceId'],
            ],
        ]);

        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \\RuntimeException('Tidak dapat menghubungi GOWA untuk menghapus device: ' . $error);
        }

        if ($http < 200 || $http >= 300) {
            log_message('error', 'GOWA device delete failed. HTTP {status}; response {body}', [
                'status' => $http,
                'body' => substr((string) $body, 0, 500),
            ]);
            throw new \\RuntimeException('GOWA gagal menghapus device (HTTP ' . $http . '). Device Portal tidak dilepas dari daftar.');
        }

        return ['httpStatus' => $http];
    }

    public function requestQr(string $deviceId): array
    {
        if (!preg_match('/^portal-[a-f0-9]{16}$/', $deviceId)) {
            throw new \InvalidArgumentException('QR hanya diizinkan untuk device milik Portal.');
        }

        $config = $this->config();
        if ($config['baseURL'] === '' || empty($config['username']) || empty($config['password'])) {
            throw new \RuntimeException('Konfigurasi GOWA belum lengkap.');
        }

        $known = false;
        foreach ($this->listDevices() as $device) {
            if (hash_equals($device['id'], $deviceId)) {
                $known = true;
                if (strtolower($device['state']) === 'logged_in') {
                    throw new \RuntimeException('Sesi WhatsApp device ini sudah berhasil dipasangkan. QR hanya dapat digunakan satu kali sampai sesi di-logout atau dihapus.');
                }
                break;
            }
        }
        if (!$known) {
            throw new \RuntimeException('Device Portal tidak ditemukan di GOWA.');
        }

        $statusUrl = $config['baseURL'] . '/devices/' . rawurlencode($deviceId) . '/status';
        $ch = curl_init($statusUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERPWD => $config['username'] . ':' . $config['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Device-Id: ' . $deviceId],
        ]);
        $statusBody = curl_exec($ch);
        $statusHttp = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($statusBody === false || $statusHttp < 200 || $statusHttp >= 300) {
            throw new \RuntimeException('Tidak dapat memeriksa status device Portal (HTTP ' . $statusHttp . ').');
        }
        $statusData = json_decode((string) $statusBody, true);
        $statusResult = is_array($statusData['results'] ?? null) ? $statusData['results'] : [];
        if (!empty($statusResult['is_logged_in'])) {
            throw new \RuntimeException('Device ini sudah login. QR tidak perlu dibuat.');
        }

        $url = $config['baseURL'] . '/devices/' . rawurlencode($deviceId) . '/login';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERPWD => $config['username'] . ':' . $config['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Device-Id: ' . $deviceId],
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $http < 200 || $http >= 300) {
            log_message('error', 'GOWA QR request failed. HTTP {status}; {error}', [
                'status' => $http,
                'error' => $error,
            ]);
            throw new \RuntimeException('GOWA gagal memulai QR (HTTP ' . $http . ').');
        }

        $data = json_decode((string) $body, true);
        $qr = is_array($data['results'] ?? null) ? $data['results'] : [];
        if (!is_array($data) || strtoupper((string) ($data['code'] ?? '')) !== 'SUCCESS' || empty($qr['qr_link'])) {
            throw new \RuntimeException('Respons QR GOWA tidak valid.');
        }

        $base = parse_url($config['baseURL']);
        $qrUrl = parse_url((string) $qr['qr_link']);
        if (!$base || !$qrUrl || empty($qrUrl['path'])) {
            throw new \RuntimeException('Alamat QR GOWA tidak valid.');
        }
        // GOWA dapat mengembalikan qr_link dengan hostname internal/container.
        // Ambil hanya path dan query, lalu selalu unduh melalui host GOWA tepercaya
        // dari konfigurasi Portal. Jangan pernah melakukan request ke host dari qr_link.
        $qrPath = (string) $qrUrl['path'];
        if (
            !str_starts_with($qrPath, '/') ||
            str_starts_with($qrPath, '//') ||
            str_contains($qrPath, chr(92)) ||
            preg_match('/[\\x00-\\x1F\\x7F]/', $qrPath)
        ) {
            throw new \RuntimeException('Path gambar QR GOWA tidak valid.');
        }

        $imageUrl = $config['baseURL'] . $qrPath
            . (isset($qrUrl['query']) ? '?' . $qrUrl['query'] : '');
        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERPWD => $config['username'] . ':' . $config['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => ['Accept: image/*', 'X-Device-Id: ' . $deviceId],
        ]);
        $image = curl_exec($ch);
        $imageHttp = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($image === false || $imageHttp < 200 || $imageHttp >= 300
            || !str_starts_with(strtolower($contentType), 'image/')
            || strlen($image) > 1024 * 1024) {
            throw new \RuntimeException('Gambar QR tidak dapat diambil dari GOWA.');
        }

        return [
            'deviceId' => $deviceId,
            'qrImage' => 'data:' . explode(';', $contentType)[0] . ';base64,' . base64_encode($image),
            'qrDuration' => (int) ($qr['qr_duration'] ?? 20),
        ];
    }

}
