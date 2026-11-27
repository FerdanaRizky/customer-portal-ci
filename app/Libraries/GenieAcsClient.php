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

        $base = parse_url($this->baseUrl);
        if (
            !is_array($base) ||
            !in_array(strtolower((string) ($base['scheme'] ?? '')), ['http', 'https'], true) ||
            empty($base['host']) ||
            isset($base['user']) ||
            isset($base['pass'])
        ) {
            throw new \RuntimeException('URL GenieACS tidak valid. Gunakan alamat HTTP atau HTTPS yang benar.');
        }

        $method = strtoupper($method);
        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new \InvalidArgumentException('Metode request GenieACS tidak didukung.');
        }

        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init($url);

        if ($ch === false) {
            throw new \RuntimeException('Tidak dapat memulai koneksi ke GenieACS.');
        }

        $headers = ['Accept: application/json'];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ];

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        if ($this->username !== null && $this->username !== '') {
            $options[CURLOPT_USERPWD] = $this->username . ':' . ($this->password ?? '');
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        }

        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException(
                'Koneksi ke GenieACS gagal (cURL ' . $curlErrno . '): ' .
                ($curlError !== '' ? $curlError : 'kesalahan jaringan atau timeout.')
            );
        }

        if ($status < 200 || $status >= 300) {
            // Jangan masukkan response body ke exception/log karena dapat
            // berisi detail internal atau data perangkat.
            throw new \RuntimeException(
                'GenieACS mengembalikan HTTP ' . $status . '.'
            );
        }

        if ($body === '') {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(
                'GenieACS mengembalikan respons JSON yang tidak valid.',
                0,
                $e
            );
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException(
                'Format respons GenieACS tidak sesuai.'
            );
        }

        return $decoded;
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
