<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\GenieAcsClient;
use App\Libraries\WhatsappGateway;

class AuthController extends BaseController
{
    private function normalizePhone(array $data): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) ($data['phone'] ?? ''));

        // Terima format lokal 08xxxxxxxxxx dan simpan/cari dengan format internasional 628xxxxxxxxxx.
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    private function auditAuth(string $event, string $phone): void
    {
        try {
            (new \App\Models\PortalModel())->log($event, $phone);
        } catch (\Throwable $e) {
            log_message('error', 'Customer auth audit write failed: {error}', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findCustomerDevice(string $phone): ?array
    {
        return (new GenieAcsClient())->findDeviceByPhone($phone);
    }

    private function logOtpDelivery(string $phone, string $status, ?string $message = null): void
    {
        try {
            $deviceId = (new \App\Libraries\ConfigStore())->get('gowa_device_id', 'billing');

            db_connect()->table('otp_delivery_logs')->insert([
                'phone' => $phone,
                'device_id' => $deviceId,
                'status' => $status,
                'message' => $message,
                'ip_address' => $this->request->getIPAddress(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'OTP delivery history write failed: {error}', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deviceName(array $device, string $phone): string
    {
        $info = $device['InternetGatewayDevice']['DeviceInfo'] ?? [];

        $manufacturer = $info['Manufacturer']['_value'] ?? '';
        $model = $info['ModelName']['_value'] ?? '';

        $name = trim($manufacturer . ' ' . $model);

        return $name !== '' ? $name : $phone;
    }

    public function requestOtp()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $phone = $this->normalizePhone($data);

        if (strlen($phone) < 9 || strlen($phone) > 15) {
            return $this->json([
                'success' => false,
                'message' => 'Nomor WhatsApp tidak valid.',
            ], 422);
        }

        // Pastikan nomor memiliki tag customer:<nomor> di GenieACS.
        try {
            $device = $this->findCustomerDevice($phone);
        } catch (\Throwable $e) {
            log_message('error', 'GenieACS request OTP error: {error}', [
                'error' => $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), 'lebih dari satu pemetaan perangkat')) {
                $this->logOtpDelivery($phone, 'mapping_conflict', 'Nomor memiliki lebih dari satu pemetaan perangkat.');
                return $this->json([
                    'success' => false,
                    'code' => 'CUSTOMER_MAPPING_CONFLICT',
                    'message' => 'Nomor WhatsApp terhubung ke lebih dari satu perangkat. Silakan hubungi administrator untuk memperbaiki pemetaan.',
                ], 409);
            }

            $this->logOtpDelivery($phone, 'system_error', 'GenieACS tidak dapat dihubungi.');
            return $this->json([
                'success' => false,
                'message' => 'Layanan GenieACS sedang tidak dapat dihubungi. Silakan coba lagi.',
            ], 503);
        }

        if (!$device || empty($device['_id'])) {
            $this->logOtpDelivery($phone, 'customer_not_found', 'Nomor tidak ditemukan di GenieACS.');
            return $this->json([
                'success' => false,
                'code' => 'CUSTOMER_NOT_FOUND',
                'message' => 'Nomor WhatsApp atau data yang Anda masukkan tidak ditemukan. Harap periksa kembali nomor WhatsApp yang terdaftar.',
            ], 422);
        }

        $db = db_connect();
        $now = date('Y-m-d H:i:s');

        // Batasi permintaan OTP: satu kali setiap 60 detik.
        $recentOtp = $db->table('otp_codes')
            ->where('phone', $phone)
            ->where('created_at >', date('Y-m-d H:i:s', time() - 60))
            ->countAllResults();

        if ($recentOtp > 0) {
            $this->logOtpDelivery($phone, 'rate_limited', 'Permintaan OTP terlalu sering.');
            return $this->json([
                'success' => false,
                'message' => 'Tunggu 60 detik sebelum meminta OTP lagi.',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);

        $db->table('otp_codes')->insert([
            'phone' => $phone,
            'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
            'expires_at' => date('Y-m-d H:i:s', time() + 300),
            'attempts' => 0,
            'created_at' => $now,
        ]);

        $otpId = $db->insertID();

        try {
            (new WhatsappGateway())->sendText(
                $phone,
                'Kode OTP Customer Portal Anda: ' . $otp .
                '. Berlaku 5 menit. Jangan bagikan kode ini kepada siapa pun.'
            );
        } catch (\Throwable $e) {
            // OTP yang gagal dikirim tidak boleh tetap bisa digunakan.
            if ($otpId) {
                $db->table('otp_codes')->where('id', $otpId)->delete();
            }

            $this->logOtpDelivery($phone, 'failed', 'Pengiriman WhatsApp gagal: ' . substr($e->getMessage(), 0, 170));
            log_message('error', 'GOWA OTP delivery error: {error}', [
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP melalui WhatsApp. Silakan coba lagi.',
            ], 503);
        }

        $this->logOtpDelivery($phone, 'sent', 'OTP berhasil dikirim melalui WhatsApp.');

        return $this->json([
            'success' => true,
            'message' => 'OTP dikirim ke WhatsApp.',
            'expiresIn' => 300,
        ]);
    }

    public function verifyOtp()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $phone = $this->normalizePhone($data);
        $otp = preg_replace('/[^0-9]/', '', (string) ($data['otp'] ?? ''));

        if (strlen($phone) < 9 || strlen($phone) > 15 || !preg_match('/^\d{6}$/', $otp)) {
            return $this->json([
                'success' => false,
                'message' => 'Nomor WhatsApp atau kode OTP tidak valid.',
            ], 422);
        }

        $db = db_connect();

        // Ambil OTP terbaru untuk nomor tersebut.
        $record = $db->table('otp_codes')
            ->where('phone', $phone)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if (!$record) {
            return $this->json([
                'success' => false,
                'message' => 'OTP tidak ditemukan. Silakan minta kode baru.',
            ], 422);
        }

        $attempts = (int) $record['attempts'];

        if ($attempts >= 3) {
            return $this->json([
                'success' => false,
                'message' => 'Batas percobaan OTP tercapai. Silakan minta kode baru.',
            ], 422);
        }

        $isExpired = strtotime($record['expires_at']) < time();
        $isCorrect = password_verify($otp, $record['otp_hash']);

        if ($isExpired || !$isCorrect) {
            $db->table('otp_codes')
                ->where('id', $record['id'])
                ->update(['attempts' => $attempts + 1]);

            return $this->json([
                'success' => false,
                'message' => $isExpired
                    ? 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.'
                    : 'Kode OTP salah.',
            ], 422);
        }

        // Periksa kembali tag GenieACS sebelum membuat sesi login.
        try {
            $device = $this->findCustomerDevice($phone);
        } catch (\Throwable $e) {
            log_message('error', 'GenieACS OTP verification error: {error}', [
                'error' => $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), 'lebih dari satu pemetaan perangkat')) {
                return $this->json([
                    'success' => false,
                    'code' => 'CUSTOMER_MAPPING_CONFLICT',
                    'message' => 'Nomor WhatsApp terhubung ke lebih dari satu perangkat. Silakan hubungi administrator.',
                ], 409);
            }

            return $this->json([
                'success' => false,
                'message' => 'Tidak dapat memverifikasi perangkat di GenieACS. Silakan coba lagi.',
            ], 503);
        }

        if (!$device || empty($device['_id'])) {
            $db->table('otp_codes')->where('id', $record['id'])->delete();

            return $this->json([
                'success' => false,
                'message' => 'Nomor tidak lagi terdaftar di GenieACS.',
            ], 403);
        }

        $deviceId = (string) $device['_id'];
        $name = $this->deviceName($device, $phone);

        session()->regenerate(true);

        session()->set('customer', [
            'id' => $deviceId,
            'phone' => $phone,
            'name' => $name,
            'deviceId' => $deviceId,
        ]);

        // OTP hanya dapat digunakan satu kali.
        $db->table('otp_codes')->where('id', $record['id'])->delete();
        $this->auditAuth('customer.login_success', $phone);

        return $this->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'redirect' => '/dashboard',
        ]);
    }

    public function logout()
    {
        $customer = session('customer');
        if (is_array($customer) && !empty($customer['phone'])) {
            $this->auditAuth('customer.logout', (string) $customer['phone']);
        }

        session()->remove('customer');
        session()->regenerate(true);

        return $this->json([
            'success' => true,
            'redirect' => '/',
        ]);
    }
}
