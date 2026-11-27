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

    private function audit(string $event, ?string $actor = null, array $detail = []): void
    {
        try {
            (new PortalModel())->log($event, $actor, $detail);
        } catch (\Throwable $e) {
            log_message('error', 'Admin audit write failed: {error}', ['error' => $e->getMessage()]);
        }
    }

    public function login()
    {
        $d = $this->request->getJSON(true) ?: $this->request->getPost();
        $username = substr(trim((string) ($d['username'] ?? '')), 0, 80);
        $ip = $this->request->getIPAddress();
        $db = db_connect();

        // Lima kegagalan dalam 15 menit memicu jeda login selama 5 menit.
        // Login sukses menjadi titik reset hitungan kegagalan sebelumnya.
        $lastSuccess = $db->table('audit_logs')
            ->select('created_at')
            ->where('event', 'admin.login_success')
            ->where('actor', $username)
            ->where('ip_address', $ip)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $windowStart = date('Y-m-d H:i:s', time() - 900);
        if ($lastSuccess && $lastSuccess['created_at'] > $windowStart) {
            $windowStart = $lastSuccess['created_at'];
        }

        $failedQuery = $db->table('audit_logs')
            ->where('event', 'admin.login_failed')
            ->where('actor', $username)
            ->where('ip_address', $ip)
            ->where('created_at >', $windowStart);

        $failedAttempts = (clone $failedQuery)->countAllResults();
        $latestFailure = $failedQuery
            ->select('created_at')
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $lockUntil = $latestFailure
            ? strtotime($latestFailure['created_at']) + 300
            : 0;

        if ($failedAttempts >= 5 && $lockUntil > time()) {
            $remaining = $lockUntil - time();
            $this->audit('admin.login_throttled', $username, [
                'windowSeconds' => 900,
                'lockSecondsRemaining' => $remaining,
            ]);
            return $this->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan login. Coba kembali dalam ' . (int) ceil($remaining / 60) . ' menit.',
            ], 429);
        }

        $r = $db->table('admins')
            ->where('username', $username)
            ->get()->getRowArray();

        if (!$r || !password_verify((string) ($d['password'] ?? ''), $r['password_hash'])) {
            $this->audit('admin.login_failed', $username, ['reason' => 'invalid_credentials']);
            return $this->json([
                'success' => false,
                'message' => 'Username atau password salah. Perhatikan: jika salah 5 kali dalam 15 menit, login akan dikunci sementara selama 5 menit.'
            ], 401);
        }

        session()->regenerate(true);
        session()->set('admin', [
            'id' => $r['id'],
            'username' => $r['username']
        ]);
        $this->audit('admin.login_success', $r['username']);

        return $this->json(['success' => true]);
    }

    public function logout()
    {
        $admin = session('admin');
        $this->audit('admin.logout', is_array($admin) ? (string) ($admin['username'] ?? '') : null);
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
        $sizeBytes = $f->getSize();
        $extensions = [
            'image/png' => ['extension' => 'png', 'type' => IMAGETYPE_PNG],
            'image/jpeg' => ['extension' => 'jpg', 'type' => IMAGETYPE_JPEG],
            'image/gif' => ['extension' => 'gif', 'type' => IMAGETYPE_GIF],
            'image/webp' => ['extension' => 'webp', 'type' => IMAGETYPE_WEBP],
        ];

        // Periksa isi file sebagai gambar sungguhan, bukan hanya MIME dari header.
        $imageInfo = @getimagesize($f->getTempName());
        if (
            !isset($extensions[$mime]) ||
            !is_array($imageInfo) ||
            ($imageInfo[2] ?? null) !== ($extensions[$mime]['type'] ?? null) ||
            (int) ($imageInfo[0] ?? 0) < 1 ||
            (int) ($imageInfo[1] ?? 0) < 1 ||
            (int) ($imageInfo[0] ?? 0) > 4096 ||
            (int) ($imageInfo[1] ?? 0) > 4096
        ) {
            return $this->json([
                'success' => false,
                'message' => 'File harus berupa gambar PNG, JPG, GIF, atau WEBP yang valid dengan ukuran dimensi maksimal 4096 x 4096 piksel.'
            ], 422);
        }

        $uploadDir = FCPATH . 'uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return $this->json([
                'success' => false,
                'message' => 'Direktori upload logo tidak tersedia.'
            ], 500);
        }

        $n = 'logo_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime]['extension'];
        if (!$f->move($uploadDir, $n)) {
            return $this->json([
                'success' => false,
                'message' => 'Logo gagal disimpan.'
            ], 500);
        }

        $url = '/uploads/' . $n;
        (new PortalModel())->saveSetting('logo_url', $url);
        $this->audit('admin.logo_uploaded', session('admin.username'), [
            'filename' => $n,
            'mime' => $mime,
            'sizeBytes' => $sizeBytes,
        ]);

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
            $filename = basename($old);
            $uploadDir = realpath(FCPATH . 'uploads');
            $oldPath = $uploadDir !== false
                ? realpath($uploadDir . DIRECTORY_SEPARATOR . $filename)
                : false;

            if (
                $oldPath !== false &&
                dirname($oldPath) === $uploadDir &&
                is_file($oldPath)
            ) {
                @unlink($oldPath);
            }
        }

        $m->saveSetting('logo_url', null);
        $this->audit('admin.logo_deleted', session('admin.username'));

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

        if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $u)) {
            return $this->json([
                'success' => false,
                'message' => 'Username harus 3-80 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.'
            ], 422);
        }

        $duplicate = db_connect()->table('admins')
            ->where('username', $u)
            ->where('id !=', $row['id'])
            ->countAllResults();

        if ($duplicate > 0) {
            return $this->json([
                'success' => false,
                'message' => 'Username sudah digunakan. Silakan pilih username lain.'
            ], 409);
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
        $this->audit('admin.credentials_updated', $u, [
            'usernameChanged' => $u !== (string) $row['username'],
            'passwordChanged' => !empty($d['newPassword']),
        ]);

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


    public function otpDeliveryLogs()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $from = trim((string) $this->request->getGet('from'));
        $to = trim((string) $this->request->getGet('to'));
        $phone = trim((string) $this->request->getGet('phone'));
        $status = trim((string) $this->request->getGet('status'));

        foreach (['from' => $from, 'to' => $to] as $field => $date) {
            if ($date !== '') {
                $parts = explode('-', $date);
                if (count($parts) !== 3
                    || !checkdate((int) ($parts[1] ?? 0), (int) ($parts[2] ?? 0), (int) ($parts[0] ?? 0))
                    || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)
                ) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Format tanggal filter tidak valid.',
                    ], 400);
                }
            }
        }

        if ($status !== '' && !in_array($status, ['success', 'failed'], true)) {
            return $this->json([
                'success' => false,
                'message' => 'Filter status tidak valid.',
            ], 400);
        }

        $builder = db_connect()->table('otp_delivery_logs');

        if ($from !== '') {
            $builder->where('created_at >=', $from . ' 00:00:00');
        }
        if ($to !== '') {
            $builder->where('created_at <=', $to . ' 23:59:59');
        }
        if ($phone !== '') {
            $builder->like('phone', $phone);
        }
        if ($status === 'success') {
            $builder->where('status', 'sent');
        } elseif ($status === 'failed') {
            $builder->whereIn('status', [
                'failed',
                'customer_not_found',
                'system_error',
                'rate_limited',
            ]);
        }

        $logs = $builder->orderBy('id', 'DESC')
            ->limit(100)
            ->get()->getResultArray();

        return $this->json([
            'success' => true,
            'logs' => $logs,
            'count' => count($logs),
        ]);
    }

    public function whatsappDevices()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        try {
            $model = new PortalModel();
            $allDevices = (new WhatsappGateway())->listDevices();
            $hidden = json_decode((string) $model->setting('gowa_hidden_devices', '[]'), true);
            $hidden = is_array($hidden) ? array_values(array_filter($hidden, 'is_string')) : [];
            $selected = $model->setting('gowa_device_id', 'billing');
            $hasLoggedInPortalSession = false;

            foreach ($allDevices as $device) {
                if (str_starts_with($device['id'], 'portal-')
                    && strtolower($device['state']) === 'logged_in') {
                    $hasLoggedInPortalSession = true;
                    break;
                }
            }

            $visibleDevices = array_values(array_filter(
                $allDevices,
                static fn ($device) => !in_array($device['id'], $hidden, true)
            ));

            return $this->json([
                'success' => true,
                'devices' => $visibleDevices,
                'hiddenDeviceIds' => $hidden,
                'hiddenDevices' => array_values(array_filter(
                    $allDevices,
                    static fn ($device) => in_array($device['id'], $hidden, true)
                )),
                'selectedDeviceId' => $selected,
                'hasLoggedInPortalSession' => $hasLoggedInPortalSession,
                'totalGowaDevices' => count($allDevices),
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 502);
        }
    }

    public function whatsappHideDevice()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $deviceId = trim((string) ($data['deviceId'] ?? ''));

        if (!preg_match('/^portal-[a-f0-9]{16}$/', $deviceId)) {
            return $this->json(['success' => false, 'message' => 'Hanya device milik Customer Portal yang dapat dihapus.'], 422);
        }

        try {
            $model = new PortalModel();
            $selected = (string) $model->setting('gowa_device_id', 'billing');
            if (hash_equals($selected, $deviceId)) {
                return $this->json(['success' => false, 'message' => 'Device ini masih dipilih sebagai pengirim Portal. Pilih device lain terlebih dahulu sebelum menghapusnya.'], 422);
            }

            $gateway = new WhatsappGateway();
            $exists = false;
            foreach ($gateway->listDevices() as $device) {
                if (hash_equals($device['id'], $deviceId)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                return $this->json(['success' => false, 'message' => 'Device tidak ditemukan di GOWA atau sudah dihapus.'], 404);
            }

            // Hapus sesi/device sungguhan di GOWA. Jika GOWA gagal, jangan ubah daftar lokal.
            $gateway->deletePortalDevice($deviceId);

            $hidden = json_decode((string) $model->setting('gowa_hidden_devices', '[]'), true);
            $hidden = is_array($hidden) ? array_values(array_filter($hidden, 'is_string')) : [];
            $hidden = array_values(array_filter($hidden, static fn ($id) => $id !== $deviceId));
            $model->saveSetting('gowa_hidden_devices', json_encode($hidden, JSON_THROW_ON_ERROR));

            $model->log('whatsapp.device_deleted', session('admin.username'), [
                'deviceId' => $deviceId,
                'action' => 'delete_from_gowa',
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Device dan sesi WhatsApp berhasil dihapus dari GOWA. Menu pairing tersedia kembali setelah daftar disinkronkan.',
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function whatsappRestoreDevice()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $deviceId = trim((string) ($data['deviceId'] ?? ''));

        if (!preg_match('/^portal-[a-f0-9]{16}$/', $deviceId)) {
            return $this->json(['success' => false, 'message' => 'Hanya device milik Customer Portal yang dapat dipulihkan.'], 422);
        }

        try {
            $model = new PortalModel();
            $allDevices = (new WhatsappGateway())->listDevices();
            $exists = false;
            foreach ($allDevices as $device) {
                if (hash_equals($device['id'], $deviceId)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                return $this->json(['success' => false, 'message' => 'Device sudah tidak ada di GOWA. Sesi yang dihapus tidak dapat dipulihkan.'], 404);
            }

            $hidden = json_decode((string) $model->setting('gowa_hidden_devices', '[]'), true);
            $hidden = is_array($hidden) ? array_values(array_filter($hidden, 'is_string')) : [];
            if (!in_array($deviceId, $hidden, true)) {
                return $this->json(['success' => false, 'message' => 'Device ini sudah tampil di daftar Portal.'], 422);
            }

            $hidden = array_values(array_filter($hidden, static fn ($id) => $id !== $deviceId));
            $model->saveSetting('gowa_hidden_devices', json_encode($hidden, JSON_THROW_ON_ERROR));
            $model->log('whatsapp.device_restored', session('admin.username'), [
                'deviceId' => $deviceId,
                'action' => 'local_restore_only',
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Device dipulihkan ke daftar Customer Portal. Sesi GOWA tidak diubah.',
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function whatsappSelectDevice()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $deviceId = trim((string) ($data['deviceId'] ?? ''));
        if ($deviceId === '') {
            return $this->json(['success' => false, 'message' => 'Pilih device WhatsApp terlebih dahulu.'], 422);
        }

        try {
            $devices = (new WhatsappGateway())->listDevices();
            $hidden = json_decode((string) (new PortalModel())->setting('gowa_hidden_devices', '[]'), true);
            $hidden = is_array($hidden) ? array_values(array_filter($hidden, 'is_string')) : [];
            if (in_array($deviceId, $hidden, true)) {
                return $this->json(['success' => false, 'message' => 'Device sudah dilepas dari daftar Portal. Sinkronkan atau buat sesi Portal baru.'], 422);
            }
            $exists = false;
            foreach ($devices as $device) {
                if (hash_equals($device['id'], $deviceId)) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                return $this->json(['success' => false, 'message' => 'Device tidak ditemukan di GOWA. Muat ulang daftar device.'], 422);
            }

            (new PortalModel())->saveSetting('gowa_device_id', $deviceId);
            (new PortalModel())->log('whatsapp.device_selected', session('admin.username'), ['deviceId' => $deviceId]);

            return $this->json(['success' => true, 'selectedDeviceId' => $deviceId]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 502);
        }
    }

    public function whatsappStatus()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        return $this->json((new WhatsappGateway())->status());
    }

    public function whatsappCreateDevice()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        try {
            $gateway = new WhatsappGateway();
            $devices = $gateway->listDevices();

            foreach ($devices as $device) {
                if (str_starts_with($device['id'], 'portal-')
                    && strtolower($device['state']) === 'logged_in') {
                    return $this->json([
                        'success' => false,
                        'message' => 'Sesi WhatsApp Portal sudah aktif. Hapus sesi tersebut sebelum membuat sesi baru.',
                    ], 409);
                }
            }

            // Pakai kembali slot Portal yang masih menunggu pairing.
            // Jangan membuat device baru hanya karena halaman dimuat ulang atau metode diganti.
            foreach ($devices as $device) {
                if (str_starts_with($device['id'], 'portal-')
                    && strtolower($device['state']) !== 'logged_in') {
                    return $this->json([
                        'success' => true,
                        'deviceId' => $device['id'],
                        'reused' => true,
                        'message' => 'Melanjutkan sesi Portal yang sudah ada. Tidak ada device baru dibuat.',
                    ]);
                }
            }

            $deviceId = 'portal-' . bin2hex(random_bytes(8));
            $result = $gateway->createPortalDevice($deviceId);
            (new PortalModel())->log(
                'whatsapp.device_created',
                session('admin.username'),
                ['deviceId' => $result['deviceId']]
            );

            return $this->json([
                'success' => true,
                'deviceId' => $result['deviceId'],
                'reused' => false,
                'message' => 'Slot device Portal berhasil dibuat satu kali. Pilih metode pairing.',
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function whatsappPairCode()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $deviceId = trim((string) ($data['deviceId'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        if (!preg_match('/^portal-[a-f0-9]{16}$/', $deviceId)) {
            return $this->json(['success' => false, 'message' => 'ID device Portal tidak valid. Buat satu device terlebih dahulu.'], 422);
        }

        try {
            $gateway = new WhatsappGateway();
            foreach ($gateway->listDevices() as $device) {
                if (str_starts_with($device['id'], 'portal-')
                    && strtolower($device['state']) === 'logged_in') {
                    return $this->json([
                        'success' => false,
                        'message' => 'Sesi WhatsApp Customer Portal masih aktif. Hapus sesi tersebut sebelum pairing baru.',
                    ], 422);
                }
            }

            $pairing = $gateway->requestPairCode($deviceId, $phone);
            (new PortalModel())->log('whatsapp.device_pair_code_requested', session('admin.username'), [
                'deviceId' => $deviceId,
                'phoneSuffix' => substr($pairing['phone'], -4),
            ]);

            return $this->json([
                'success' => true,
                'deviceId' => $deviceId,
                'pairCode' => $pairing['pairCode'],
                'message' => 'Kode pairing berhasil dibuat untuk device yang sama. Masukkan kode tersebut di WhatsApp pada ponsel.',
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function whatsappQr()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $deviceId = trim((string) ($data['deviceId'] ?? ''));

        try {
            return $this->json([
                'success' => true,
                ...(new WhatsappGateway())->requestQr($deviceId),
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

}
