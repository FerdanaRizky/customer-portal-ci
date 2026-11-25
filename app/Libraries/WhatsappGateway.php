<?php
namespace App\Libraries;

/**
 * Client untuk GOWA Pesona yang sudah berjalan.
 * Tidak menjalankan / memasang WhatsApp gateway di aplikasi Customer Portal.
 */
class WhatsappGateway
{
    private function config(): array
    {
        return [
            'baseURL' => rtrim((string) env('gowa.baseURL', ''), '/'),
            'username' => (string) env('gowa.username', ''),
            'password' => (string) env('gowa.password', ''),
            'deviceId' => (string) env('gowa.deviceId', 'billing'),
        ];
    }

    /**
     * Mengirim pesan teks melalui GOWA.
     *
     * @return array Respons JSON dari GOWA.
     */
    public function sendText(string $phone, string $message): array
    {
        $config = $this->config();
        if ($config['baseURL'] === '' || $config['username'] === '' ||
            $config['password'] === '' || $config['deviceId'] === '') {
            throw new \RuntimeException('Konfigurasi GOWA belum lengkap. Periksa gowa.baseURL, gowa.username, gowa.password, dan gowa.deviceId di .env.');
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') {
            throw new \InvalidArgumentException('Nomor WhatsApp penerima tidak valid.');
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
            log_message('error', 'GOWA cURL error: {error}', ['error' => $error]);
            throw new \RuntimeException('Tidak dapat menghubungi GOWA: ' . $error);
        }

        $decoded = json_decode((string) $body, true);
        $hasError = is_array($decoded) && (
            isset($decoded['error']) ||
            isset($decoded['errors']) ||
            (isset($decoded['code']) && strtoupper((string) $decoded['code']) !== 'SUCCESS')
        );

        if ($status < 200 || $status >= 300 || $hasError) {
            log_message('error', 'GOWA send failed. HTTP {status}; response {body}', [
                'status' => $status,
                'body' => substr((string) $body, 0, 700),
            ]);
            throw new \RuntimeException('GOWA gagal mengirim pesan (HTTP ' . $status . '). Periksa koneksi device dan log GOWA.');
        }

        log_message('info', 'GOWA accepted message using device {device}.', [
            'device' => $config['deviceId'],
        ]);

        return is_array($decoded) ? $decoded : ['raw' => (string) $body];
    }

    /**
     * GOWA Cloud Drive tidak menggunakan endpoint status terpisah.
     * Status device hanya dapat dipastikan dari respons pengiriman pesan.
     */
    public function status(): array
    {
        $config = $this->config();
        $configured = $config['baseURL'] !== '' &&
            $config['username'] !== '' &&
            $config['password'] !== '' &&
            $config['deviceId'] !== '';

        return [
            'configured' => $configured,
            'connected' => null,
            'deviceId' => $config['deviceId'],
            'baseURL' => $config['baseURL'],
            'message' => $configured
                ? 'Konfigurasi GOWA tersedia. Status koneksi device diverifikasi saat pengiriman pesan.'
                : 'Konfigurasi GOWA belum lengkap.',
        ];
    }
}
