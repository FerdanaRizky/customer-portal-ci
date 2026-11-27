<?php

namespace App\Commands;

use App\Libraries\ConfigStore;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MigrateEnvSettings extends BaseCommand
{
    protected $group = 'Portal';
    protected $name = 'portal:migrate-env-settings';
    protected $description = 'Copy operational settings from env to database';

    public function run(array $params)
    {
        $store = new ConfigStore();
        $plainCount = 0;
        $secretCount = 0;
        $skippedCount = 0;

        $readEnv = static function (string $name): string {
            $value = env($name, null);

            if ($value === null) {
                $value = getenv($name);
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            return is_string($value) || is_numeric($value)
                ? trim((string) $value)
                : '';
        };

        $plainSettings = [
            'app.baseURL' => 'app_url',
            'app.forceGlobalSecureRequests' => 'force_https',
            'GENIEACS_URL' => 'genieacs_url',
            'gowa.baseURL' => 'gowa_base_url',
            'gowa.deviceId' => 'gowa_device_id',
        ];

        foreach ($plainSettings as $envKey => $dbKey) {
            $value = $readEnv($envKey);

            if ($value === '') {
                $skippedCount++;
                continue;
            }

            $existing = $store->get($dbKey);

            if ($existing !== null && $existing !== '') {
                $skippedCount++;
                continue;
            }

            $store->set($dbKey, $value);
            $plainCount++;
        }

        $secretSettings = [
            'GENIEACS_USERNAME' => 'genieacs_username',
            'GENIEACS_PASSWORD' => 'genieacs_password',
            'gowa.username' => 'gowa_username',
            'gowa.password' => 'gowa_password',
        ];

        foreach ($secretSettings as $envKey => $dbKey) {
            $value = $readEnv($envKey);

            if ($value === '') {
                $skippedCount++;
                continue;
            }

            $existing = $store->get($dbKey);

            if ($existing !== null && $existing !== '') {
                $skippedCount++;
                continue;
            }

            $store->setSecret($dbKey, $value);
            $secretCount++;
        }

        CLI::write('MIGRASI KONFIGURASI SELESAI', 'green');
        CLI::write('Pengaturan biasa disalin: ' . $plainCount);
        CLI::write('Kredensial terenkripsi: ' . $secretCount);
        CLI::write('Dilewati/kosong/sudah tersedia: ' . $skippedCount);
        CLI::write('Nilai konfigurasi dan password tidak ditampilkan.');
        CLI::write('ADMIN_USERNAME dan ADMIN_PASSWORD tidak disalin.');
    }
}
