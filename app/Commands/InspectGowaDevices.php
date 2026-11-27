<?php

namespace App\Commands;

use App\Libraries\ConfigStore;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class InspectGowaDevices extends BaseCommand
{
    protected $group = 'Portal';
    protected $name = 'portal:inspect-gowa-devices';
    protected $description = 'Read-only inspection of GOWA device IDs';

    public function run(array $params)
    {
        $store = new ConfigStore();
        $url = rtrim((string) $store->get('gowa_base_url', ''), '/');
        $user = $store->getSecret('gowa_username');
        $pass = $store->getSecret('gowa_password');

        $ch = curl_init($url . '/devices');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $user . ':' . $pass,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false || $http < 200 || $http >= 300) {
            CLI::error('GOWA HTTP ' . $http);
            return;
        }


        $data = json_decode((string) $body, true);

        CLI::write('GOWA HTTP: ' . $http, 'green');

        if (!is_array($data)) {
            CLI::error('Respons bukan JSON object/array.');
            return;
        }

        CLI::write('ROOT KEYS: ' . implode(', ', array_keys($data)));

        $items = $data['results'] ?? null;

        CLI::write('RESULTS TYPE: ' . gettype($items));

        if (is_array($items)) {
            CLI::write('RESULTS KEYS: ' . implode(', ', array_keys($items)));

            foreach ($items as $key => $item) {
                if (is_array($item)) {
                    CLI::write('ITEM ' . $key . ' KEYS: ' . implode(', ', array_keys($item)));

                    foreach (['device_id', 'id', 'name', 'state'] as $field) {
                        if (isset($item[$field]) && is_scalar($item[$field])) {
                            CLI::write('  ' . $field . ': ' . $item[$field]);
                        }
                    }
                } elseif (is_scalar($item)) {
                    CLI::write('ITEM ' . $key . ' TYPE: ' . gettype($item));
                }
            }
        }

    }
}
