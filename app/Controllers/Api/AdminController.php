<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PortalModel;
use App\Libraries\ConfigStore;
use App\Libraries\WhatsappGateway;

class AdminController extends BaseController
{
    private function guard()
    {
        return $this->requireAdmin()
            ? null
            : $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    public function login()
    {
        $d = $this->request->getJSON(true) ?: $this->request->getPost();

        $r = db_connect()->table('admins')
            ->where('username', trim((string) ($d['username'] ?? '')))
            ->get()->getRowArray();

        if (!$r || !password_verify((string) ($d['password'] ?? ''), $r['password_hash'])) {
            return $this->json([
                'success' => false,
                'message' => 'Username atau password salah.'
            ], 401);
        }

        session()->regenerate(true);
        session()->set('admin', [
            'id' => $r['id'],
            'username' => $r['username']
        ]);

        return $this->json(['success' => true]);
    }

    public function logout()
    {
        session()->remove('admin');
        session()->regenerate(true);

        return $this->json(['success' => true]);
    }

    public function status()
    {
        return $this->guard()
            ?? $this->json(['success' => true, 'admin' => session('admin')]);
    }

    public function settings()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $m = new PortalModel();
        $store = new ConfigStore();

        return $this->json([
            'appName' => $m->setting('app_name', 'Pesona Wifi'),
            'appUrl' => $m->setting('app_url', ''),
            'logoUrl' => $m->setting('logo_url'),
            'genieacsUrl' => $m->setting('genieacs_url', ''),
            'ssidIndexes' => json_decode(
                $m->setting('ssid_indexes', '[1,5]'),
                true
            ),
            'forceHttps' => filter_var(
                $store->get('force_https', 'false'),
                FILTER_VALIDATE_BOOLEAN
            ),
            'gowaBaseUrl' => $store->get('gowa_base_url', ''),
            'gowaDeviceId' => $store->get('gowa_device_id', ''),
            'genieacsUsernameConfigured' => $store->hasSecret('genieacs_username'),
            'genieacsPasswordConfigured' => $store->hasSecret('genieacs_password'),
            'gowaUsernameConfigured' => $store->hasSecret('gowa_username'),
            'gowaPasswordConfigured' => $store->hasSecret('gowa_password')
        ]);
    }

    public function saveSettings()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $d = $this->request->getJSON(true) ?: $this->request->getPost();
        $m = new PortalModel();
        $store = new ConfigStore();

        $plainFields = [
            'appName' => 'app_name',
            'appUrl' => 'app_url',
            'genieacsUrl' => 'genieacs_url',
            'gowaBaseUrl' => 'gowa_base_url',
            'gowaDeviceId' => 'gowa_device_id'
        ];

        foreach ($plainFields as $input => $key) {
            if (array_key_exists($input, $d)) {
                $value = trim((string) $d[$input]);
                $m->saveSetting($key, $value);
            }
        }

        if (array_key_exists('ssidIndexes', $d)) {
            $indexes = is_array($d['ssidIndexes']) ? $d['ssidIndexes'] : [];

            $indexes = array_values(array_unique(array_filter(
                array_map('intval', $indexes),
                static fn ($v) => $v >= 1 && $v <= 8
            )));

            $m->saveSetting(
                'ssid_indexes',
                json_encode($indexes)
            );
        }

        if (array_key_exists('forceHttps', $d)) {
            $value = filter_var(
                $d['forceHttps'],
                FILTER_VALIDATE_BOOLEAN
            ) ? 'true' : 'false';

            $store->set('force_https', $value);
        }

        $secretFields = [
            'genieacsUsername' => 'genieacs_username',
            'genieacsPassword' => 'genieacs_password',
            'gowaUsername' => 'gowa_username',
            'gowaPassword' => 'gowa_password'
        ];

        foreach ($secretFields as $input => $key) {
            if (isset($d[$input]) && trim((string) $d[$input]) !== '') {
                $store->setSecret($key, trim((string) $d[$input]));
            }
        }

        // Audit log hanya menyimpan nama pengaturan yang diubah.
        // Tidak pernah mencatat password atau kredensial.
        $safeChanges = array_values(array_intersect(
            array_keys($d),
            [
                'appName',
                'appUrl',
                'genieacsUrl',
                'ssidIndexes',
                'forceHttps',
                'gowaBaseUrl',
                'gowaDeviceId'
            ]
        ));

        foreach (array_keys($secretFields) as $secretInput) {
            if (isset($d[$secretInput]) && trim((string) $d[$secretInput]) !== '') {
                $safeChanges[] = $secretInput . ':updated';
            }
        }

        $m->log(
            'settings.updated',
            session('admin.username'),
            ['changed' => $safeChanges]
        );

        return $this->json(['success' => true]);
    }

    public function uploadLogo()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $f = $this->request->getFile('logo');

        if (!$f || !$f->isValid() || $f->getSizeByUnit('mb') > 2) {
            return $this->json([
                'success' => false,
                'message' => 'File tidak valid atau lebih dari 2 MB.'
            ], 422);
        }

        $mime = $f->getMimeType();
        $extensions = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        if (!isset($extensions[$mime])) {
            return $this->json([
                'success' => false,
                'message' => 'Format tidak didukung.'
            ], 422);
        }

        $n = 'logo_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        $f->move(FCPATH . 'uploads', $n);

        $url = '/uploads/' . $n;
        (new PortalModel())->saveSetting('logo_url', $url);

        return $this->json([
            'success' => true,
            'logoUrl' => $url
        ]);
    }

    public function deleteLogo()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $m = new PortalModel();
        $old = $m->setting('logo_url');

        if ($old && str_starts_with($old, '/uploads/')) {
            @unlink(FCPATH . ltrim($old, '/'));
        }

        $m->saveSetting('logo_url', null);

        return $this->json(['success' => true]);
    }

    public function credentials()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $d = $this->request->getJSON(true) ?: $this->request->getPost();
        $a = session('admin');

        $row = db_connect()->table('admins')
            ->where('id', $a['id'])
            ->get()->getRowArray();

        if (!$row || !password_verify(
            (string) ($d['currentPassword'] ?? ''),
            $row['password_hash']
        )) {
            return $this->json([
                'success' => false,
                'message' => 'Password saat ini salah.'
            ], 422);
        }

        $u = trim((string) ($d['username'] ?? $row['username']));

        if ($u === '') {
            return $this->json([
                'success' => false,
                'message' => 'Username tidak boleh kosong.'
            ], 422);
        }

        $up = ['username' => $u];

        if (!empty($d['newPassword'])) {
            if (strlen($d['newPassword']) < 8) {
                return $this->json([
                    'success' => false,
                    'message' => 'Password minimal 8 karakter.'
                ], 422);
            }

            $up['password_hash'] = password_hash(
                $d['newPassword'],
                PASSWORD_DEFAULT
            );
        }

        db_connect()->table('admins')->where('id', $row['id'])->update($up);
        session()->set('admin.username', $u);

        return $this->json(['success' => true]);
    }

    public function customers()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        return $this->json([
            'success' => true,
            'customers' => db_connect()->table('customers')
                ->orderBy('id', 'DESC')
                ->get()->getResultArray()
        ]);
    }

    public function logs()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        return $this->json([
            'success' => true,
            'logs' => db_connect()->table('audit_logs')
                ->orderBy('id', 'DESC')
                ->limit(100)
                ->get()->getResultArray()
        ]);
    }

    public function whatsappStatus()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        return $this->json((new WhatsappGateway())->status());
    }

    public function whatsappQr()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        try {
            return $this->json([
                'success' => true,
                ...(new WhatsappGateway())->requestQr(),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

}
