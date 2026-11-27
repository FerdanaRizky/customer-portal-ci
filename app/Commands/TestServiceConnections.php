<?php

namespace App\Commands;

use App\Libraries\ConfigStore;
use App\Libraries\GenieAcsClient;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestServiceConnections extends BaseCommand
{
    protected $group = 'Portal';
    protected $name = 'portal:test-service-connections';
    protected $description = 'Test GenieACS and GOWA using database configuration';

    public function run(array $params)
    {
        $store = new ConfigStore();

        // GenieACS: GET saja, tidak mengubah perangkat.
        try {
            $client = new GenieAcsClient();
            $client->request(
                'GET',
                'devices/?query=%7B%7D&projection=_id&limit=1'
            );

            CLI::write('GENIEACS: KONEKSI BERHASIL', 'green');
        } catch (\Throwable $e) {
            CLI::error('GENIEACS: GAGAL (' . get_class($e) . ')');
            log_message('error', 'GenieACS connection test failed: {message}', [
                'message' => $e->getMessage(),
            ]);
        }

        // GOWA: GET status saja, tidak mengirim pesan atau logout.
        try {
            $baseURL = rtrim((string) $store->get('gowa_base_url', ''), '/');
            $username = $store->getSecret('gowa_username');
            $password = $store->getSecret('gowa_password');
            $deviceId = (string) $store->get('gowa_device_id', '');

            if (!$baseURL || !$username || !$password || !$deviceId) {
                throw new \RuntimeException('Konfigurasi belum lengkap.');
            }

            $ch = curl_init(
                $baseURL . '/devices/' . rawurlencode($deviceId) . '/status'
            );

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPGET => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERPWD => $username . ':' . $password,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'X-Device-Id: ' . $deviceId,
                ],
            ]);

            $body = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($body === false || $http < 200 || $http >= 300) {
                throw new \RuntimeException(
                    'HTTP ' . $http . ($error ? ' / cURL error' : '')
                );
            }

            $result = json_decode((string) $body, true);

            if (
                !is_array($result) ||
                (isset($result['code']) &&
                    strtoupper((string) $result['code']) !== 'SUCCESS')
            ) {
                throw new \RuntimeException('Respons status GOWA tidak valid.');
            }

            CLI::write('GOWA: KONEKSI BERHASIL (HTTP ' . $http . ')', 'green');
        } catch (\Throwable $e) {
            CLI::error('GOWA: GAGAL (' . get_class($e) . ')');
            log_message('error', 'GOWA connection test failed: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
