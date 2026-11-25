<?php
namespace App\Libraries;

class WhatsappGateway
{
    public function sendText(string $phone, string $message): array
    {
        $url = rtrim(getenv('WHATSAPP_GATEWAY_URL') ?: '', '/');
        $token = getenv('WHATSAPP_GATEWAY_TOKEN') ?: '';
        if ($url === '') {
            throw new \RuntimeException('WHATSAPP_GATEWAY_URL belum dikonfigurasi. Gateway Baileys asli pada aplikasi Node.js tidak dapat dijalankan di PHP; gunakan gateway HTTP eksternal.');
        }
        $ch = curl_init($url . '/send-message');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
            CURLOPT_POSTFIELDS => json_encode(['phone' => $phone, 'message' => $message], JSON_THROW_ON_ERROR),
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false || $status >= 400) throw new \RuntimeException('WhatsApp gateway gagal (' . $status . '): ' . $error);
        return json_decode((string) $body, true) ?: ['raw' => $body];
    }

    public function status(): array
    {
        $url = rtrim(getenv('WHATSAPP_GATEWAY_URL') ?: '', '/');
        if (!$url) return ['connected' => false, 'configured' => false];
        $ch = curl_init($url . '/status');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 5]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ['configured' => true, 'connected' => $status >= 200 && $status < 300, 'response' => json_decode((string) $body, true)];
    }
}
