<?php

namespace App\Libraries;

class PortalDisplayConfig
{
    public static function defaults(): array
    {
        return [
            'sections' => [
                'deviceInfo' => true,
                'wifi' => true,
            ],
            'fields' => [
                'phone' => [
                    'visible' => true,
                    'label' => 'WhatsApp',
                    'order' => 1,
                ],
                'product' => [
                    'visible' => true,
                    'label' => 'Product Class',
                    'order' => 2,
                ],
                'serial' => [
                    'visible' => true,
                    'label' => 'Serial Number',
                    'order' => 3,
                ],
                'lastInform' => [
                    'visible' => true,
                    'label' => 'Last Inform',
                    'order' => 4,
                ],
                'manufacturer' => [
                    'visible' => true,
                    'label' => 'Manufacturer',
                    'order' => 5,
                ],
                'accessType' => [
                    'visible' => true,
                    'label' => 'Jenis Koneksi',
                    'order' => 6,
                ],
                'connected' => [
                    'visible' => true,
                    'label' => 'Perangkat Terhubung',
                    'order' => 7,
                ],
                'rxPower' => [
                    'visible' => true,
                    'label' => 'Optical RX Power',
                    'order' => 8,
                ],
            ],
            'features' => [
                'refresh' => true,
                'reboot' => true,
                'updateSsid' => true,
                'updatePassword' => true,
            ],
        ];
    }

    public static function normalize(?string $json): array
    {
        $defaults = self::defaults();
        $saved = json_decode((string) $json, true);

        if (!is_array($saved)) {
            return $defaults;
        }

        foreach (['deviceInfo', 'wifi'] as $key) {
            if (isset($saved['sections'][$key])) {
                $defaults['sections'][$key] =
                    (bool) $saved['sections'][$key];
            }
        }

        foreach ($defaults['fields'] as $key => $field) {
            $input = $saved['fields'][$key] ?? [];

            if (array_key_exists('visible', $input)) {
                $defaults['fields'][$key]['visible'] =
                    (bool) $input['visible'];
            }

            if (isset($input['label']) && is_string($input['label'])) {
                $label = trim($input['label']);

                if ($label !== '') {
                    $defaults['fields'][$key]['label'] =
                        mb_substr($label, 0, 50);
                }
            }

            if (isset($input['order']) && is_numeric($input['order'])) {
                $defaults['fields'][$key]['order'] =
                    max(1, min(99, (int) $input['order']));
            }
        }

        foreach ($defaults['features'] as $key => $value) {
            if (array_key_exists($key, $saved['features'] ?? [])) {
                $defaults['features'][$key] =
                    (bool) $saved['features'][$key];
            }
        }

        return $defaults;
    }
}
