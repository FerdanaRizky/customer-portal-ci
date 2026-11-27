<?php

namespace App\Libraries;

use RuntimeException;

class ConfigStore
{
    private $db;
    private $encrypter;

    private const SECRET_PREFIX = 'enc:v1:';

    public function __construct()
    {
        $this->db = db_connect();
        $this->encrypter = service('encrypter');
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $row = $this->db->table('settings')
            ->select('setting_value')
            ->where('setting_key', $key)
            ->get()
            ->getRowArray();

        return $row['setting_value'] ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        $builder = $this->db->table('settings');

        $row = $builder->select('id')
            ->where('setting_key', $key)
            ->get()
            ->getRowArray();

        $data = [
            'setting_value' => $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($row) {
            $this->db->table('settings')
                ->where('id', $row['id'])
                ->update($data);
        } else {
            $this->db->table('settings')->insert([
                'setting_key' => $key,
                ...$data,
            ]);
        }
    }

    public function setSecret(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            $this->set($key, null);
            return;
        }

        $encrypted = $this->encrypter->encrypt($value);

        $this->set(
            $key,
            self::SECRET_PREFIX . base64_encode($encrypted)
        );
    }

    public function getSecret(
        string $key,
        ?string $fallback = null
    ): ?string {
        $stored = $this->get($key);

        if ($stored === null || $stored === '') {
            return $fallback;
        }

        if (!str_starts_with($stored, self::SECRET_PREFIX)) {
            throw new RuntimeException(
                'Format penyimpanan kredensial tidak valid.'
            );
        }

        $encoded = substr($stored, strlen(self::SECRET_PREFIX));
        $encrypted = base64_decode($encoded, true);

        if ($encrypted === false) {
            throw new RuntimeException(
                'Data kredensial terenkripsi tidak valid.'
            );
        }

        return $this->encrypter->decrypt($encrypted);
    }

    public function hasSecret(string $key): bool
    {
        $stored = $this->get($key);

        return $stored !== null
            && str_starts_with($stored, self::SECRET_PREFIX);
    }
}
