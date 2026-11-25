<?php
namespace App\Models;

use CodeIgniter\Model;

class PortalModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'settings';
    protected $primaryKey = 'id';
    protected $allowedFields = ['setting_key', 'setting_value', 'updated_at'];

    public function setting(string $key, ?string $default = null): ?string
    {
        $row = $this->db->table('settings')->where('setting_key', $key)->get()->getRowArray();
        return $row['setting_value'] ?? $default;
    }

    public function saveSetting(string $key, ?string $value): void
    {
        $builder = $this->db->table('settings');
        $exists = $builder->where('setting_key', $key)->countAllResults();
        $data = ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')];
        if ($exists) $builder->where('setting_key', $key)->update($data);
        else $this->db->table('settings')->insert(['setting_key' => $key] + $data);
    }

    public function log(string $event, ?string $actor = null, array|string|null $detail = null): void
    {
        $this->db->table('audit_logs')->insert([
            'event' => $event,
            'actor' => $actor,
            'detail' => is_array($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE) : $detail,
            'ip_address' => service('request')->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
