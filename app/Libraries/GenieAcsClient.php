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

    public function findDeviceByPhone(string $phone): ?array
    {
        $filter = rawurlencode(json_encode([
            '_tags' => ['customer:' . $phone],
        ]));

        $result = $this->request(
            'GET',
            'devices/?query=' . $filter .
            '&projection=_id,_deviceId,_lastInform,InternetGatewayDevice'
        );

        return $result[0] ?? null;
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
