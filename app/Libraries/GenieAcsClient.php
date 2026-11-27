<?php

namespace App\Libraries;

class GenieAcsClient
{
    private string $baseUrl;
    private ?string $username;
    private ?string $password;

    public function __construct(?string $baseUrl = null)
    {
        $store = new ConfigStore();

        $envUrl = (string) env('GENIEACS_URL', '');
        $envUsername = (string) env('GENIEACS_USERNAME', '');
        $envPassword = (string) env('GENIEACS_PASSWORD', '');

        $this->baseUrl = rtrim(
            $baseUrl ?: ($store->get('genieacs_url') ?: $envUrl),
            '/'
        );

        $this->username = $store->getSecret(
            'genieacs_username',
            $envUsername !== '' ? $envUsername : null
        );

        $this->password = $store->getSecret(
            'genieacs_password',
            $envPassword !== '' ? $envPassword : null
        );
    }

    public function request(string $method, string $path, array $payload = []): array
    {
        if ($this->baseUrl === '') {
            throw new \RuntimeException('URL GenieACS belum dikonfigurasi.');
        }

        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init($url);

        $headers = ['Accept: application/json'];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        if ($this->username !== null) {
            $options[CURLOPT_USERPWD] = $this->username . ':' . ($this->password ?? '');
        }

        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($body === false || $status >= 400) {
            throw new \RuntimeException(
                'GenieACS request gagal (' . $status . '): ' .
                ($error ?: (string) $body)
            );
        }

        $decoded = json_decode((string) $body, true);

        return is_array($decoded) ? $decoded : ['raw' => $body];
    }

    /**
     * Cari perangkat berdasarkan tag customer dan pastikan pemetaannya unik.
     * Jangan pernah mengambil perangkat pertama jika satu nomor terpasang
     * pada lebih dari satu perangkat GenieACS.
     */
    public function findDeviceByPhone(string $phone): ?array
    {
        $tag = 'customer:' . $phone;
        $filter = rawurlencode(json_encode(['_tags' => [$tag]]));

        $result = $this->request(
            'GET',
            'devices/?query=' . $filter .
            '&projection=_id,_deviceId,_tags,_lastInform,InternetGatewayDevice'
        );

        $matches = array_values(array_filter(
            $result,
            static fn ($device) =>
                is_array($device) &&
                !empty($device['_id']) &&
                in_array($tag, $device['_tags'] ?? [], true)
        ));

        if (count($matches) > 1) {
            throw new \RuntimeException(
                'Nomor WhatsApp memiliki lebih dari satu pemetaan perangkat di GenieACS.'
            );
        }

        return $matches[0] ?? null;
    }

    /**
     * Validasi bahwa perangkat pada sesi pelanggan masih ada dan masih
     * memiliki tag nomor WhatsApp yang sama.
     */
    public function findDeviceByIdAndPhone(string $deviceId, string $phone): ?array
    {
        if ($deviceId === '' || $phone === '') {
            return null;
        }

        $tag = 'customer:' . $phone;
        $filter = rawurlencode(json_encode(['_tags' => [$tag]]));

        $result = $this->request(
            'GET',
            'devices/?query=' . $filter . '&projection=_id,_tags'
        );

        $matches = array_values(array_filter(
            $result,
            static fn ($device) =>
                is_array($device) &&
                !empty($device['_id']) &&
                in_array($tag, $device['_tags'] ?? [], true)
        ));

        // Tolak sesi jika nomor tidak lagi dipetakan secara unik ke device
        // yang sama dengan device ID saat OTP diverifikasi.
        if (count($matches) !== 1) {
            return null;
        }

        $device = $matches[0];

        return (string) $device['_id'] === $deviceId ? $device : null;
    }

    public function command(string $deviceId, string $name, array $parameters = []): array
    {
        return $this->request(
            'POST',
            'devices/' . rawurlencode($deviceId) .
            '/tasks?timeout=3000&connection_request',
            [
                'name' => $name,
                'parameterValues' => $parameters,
            ]
        );
    }
}
