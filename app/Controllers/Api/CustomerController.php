<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\GenieAcsClient;

class CustomerController extends BaseController
{
    private function client(): GenieAcsClient
    {
        return new GenieAcsClient();
    }

    private function auditCustomer(string $event, array $customer, array $detail = []): void
    {
        try {
            (new \\App\\Models\\PortalModel())->log(
                $event,
                (string) ($customer['phone'] ?? 'customer'),
                ['deviceId' => (string) ($customer['deviceId'] ?? ''), ...$detail]
            );
        } catch (\\Throwable $e) {
            log_message('error', 'Customer audit write failed: {error}', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ikat setiap akses API ke kombinasi nomor WhatsApp + device ID
     * yang diverifikasi saat OTP. Jika tag berubah atau device dihapus,
     * sesi lokal langsung dicabut.
     */
    private function validateCustomerMapping(array $customer)
    {
        $phone = (string) ($customer['phone'] ?? '');
        $deviceId = (string) ($customer['deviceId'] ?? '');

        if ($phone === '' || $deviceId === '') {
            session()->remove('customer');
            return $this->json([
                'success' => false,
                'message' => 'Sesi pelanggan tidak valid. Silakan login kembali dengan OTP.',
            ], 401);
        }

        try {
            $device = $this->client()->findDeviceByIdAndPhone($deviceId, $phone);
        } catch (\Throwable $e) {
            log_message('error', 'Customer mapping validation failed: {error}', [
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Belum dapat memeriksa data perangkat di GenieACS. Silakan coba lagi.',
            ], 503);
        }

        if (!$device) {
            session()->remove('customer');
            session()->regenerate(true);

            return $this->json([
                'success' => false,
                'message' => 'Perangkat atau hubungan nomor WhatsApp sudah tidak ditemukan di GenieACS. Silakan login kembali setelah data diperbaiki.',
                'code' => 'CUSTOMER_DEVICE_NOT_FOUND',
            ], 401);
        }

        return null;
    }

    /**
     * Mengambil nilai _value dari parameter GenieACS.
     */
    private function parameterValue(array $data, array $path, $default = null)
    {
        $current = $data;

        foreach ($path as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }

            $current = $current[$key];
        }

        if (is_array($current) && array_key_exists('_value', $current)) {
            return $current['_value'];
        }

        return $default;
    }

    public function me()
    {
        $customer = $this->requireCustomer();

        if (!$customer) {
            return $this->json(['success' => false], 401);
        }

        if ($guard = $this->validateCustomerMapping($customer)) {
            return $guard;
        }

        return $this->json([
                'success' => true,
                'customer' => $customer,
            ]);
    }

    public function device()
    {
        $customer = $this->requireCustomer();

        if (!$customer) {
            return $this->json(['success' => false], 401);
        }

        if ($guard = $this->validateCustomerMapping($customer)) {
            return $guard;
        }

        $deviceId = (string) ($customer['deviceId'] ?? '');

        if ($deviceId === '') {
            return $this->json([
                'success' => false,
                'message' => 'Perangkat belum dikaitkan dengan akun ini.',
            ], 404);
        }

        try {
            $query = rawurlencode(json_encode(
                ['_id' => $deviceId],
                JSON_THROW_ON_ERROR
            ));

            $result = $this->client()->request(
                'GET',
                'devices/?query=' . $query .
                '&projection=_id,_deviceId,_lastInform,InternetGatewayDevice.DeviceInfo,InternetGatewayDevice.LANDevice.1.WLANConfiguration,InternetGatewayDevice.WANDevice.1.WANCommonInterfaceConfig,InternetGatewayDevice.WANDevice.1.WANConnectionDevice,InternetGatewayDevice.WANDevice.1.X_CT-COM_EponInterfaceConfig,VirtualParameters.RXPower'
            );

            $raw = $result[0] ?? null;

            if (!$raw) {
                return $this->json([
                    'success' => false,
                    'message' => 'Perangkat tidak ditemukan di GenieACS.',
                ], 404);
            }

            $igd = $raw['InternetGatewayDevice'] ?? [];
            $deviceIdInfo = $raw['_deviceId'] ?? [];

            $manufacturer = $this->parameterValue(
                $igd,
                ['DeviceInfo', 'Manufacturer'],
                $deviceIdInfo['_Manufacturer'] ?? null
            );

            $model = $this->parameterValue(
                $igd,
                ['DeviceInfo', 'ModelName'],
                $deviceIdInfo['_ProductClass'] ?? null
            );

            $serial = $this->parameterValue(
                $igd,
                ['DeviceInfo', 'SerialNumber'],
                $deviceIdInfo['_SerialNumber'] ?? null
            );

            $firmware = $this->parameterValue(
                $igd,
                ['DeviceInfo', 'SoftwareVersion']
            );

            $uptime = $this->parameterValue(
                $igd,
                ['DeviceInfo', 'UpTime']
            );

            $wifiConfig = $igd['LANDevice']['1']['WLANConfiguration'] ?? [];
            $wifiList = [];

            foreach ($wifiConfig as $index => $wifi) {
                if (!is_array($wifi) || !is_numeric($index)) {
                    continue;
                }

                $ssid = $this->parameterValue($wifi, ['SSID']);

                if ($ssid === null) {
                    continue;
                }

                $wifiList[] = [
                    'index' => (int) $index,
                    'ssid' => $ssid,
                    'enabled' => (bool) $this->parameterValue(
                        $wifi,
                        ['Enable'],
                        false
                    ),
                    'connectedDevices' => (int) $this->parameterValue(
                        $wifi,
                        ['TotalAssociations'],
                        0
                    ),
                    'security' => $this->parameterValue(
                        $wifi,
                        ['BeaconType']
                    ),
                ];
            }

            $wan = $igd['WANDevice']['1'] ?? [];

            $accessType = $this->parameterValue(
                $wan,
                ['WANCommonInterfaceConfig', 'WANAccessType']
            );

            // Prioritaskan VirtualParameters.RXPower dari GenieACS.
            $rxPower = $this->parameterValue(
                $raw,
                ['VirtualParameters', 'RXPower']
            );

            // Gunakan parameter bawaan perangkat sebagai cadangan.
            if ($rxPower === null) {
                $rxPower = $this->parameterValue(
                    $igd,
                    ['WANDevice', '1', 'X_CT-COM_EponInterfaceConfig', 'RXPower']
                );
            }

            $connectedDevices = 0;

            foreach ($wifiList as $wifi) {
                if ($wifi['enabled']) {
                    $connectedDevices += $wifi['connectedDevices'];
                }
            }

            // Hanya kirim data yang sudah dipilih.
            // Jangan mengirim seluruh InternetGatewayDevice ke browser.
            $safeDevice = [
                'id' => $raw['_id'] ?? $deviceId,
                'manufacturer' => $manufacturer,
                'model' => $model,
                'serialNumber' => $serial,
                'firmware' => $firmware,
                'uptime' => $uptime,
                'lastInform' => $raw['_lastInform'] ?? null,
                'connection' => [
                    'accessType' => $accessType,
                    'opticalRxPowerRaw' => $rxPower,
                ],
                'wifi' => $wifiList,
                'connectedDevices' => $connectedDevices,
            ];

            return $this->json([
                'success' => true,
                'device' => $safeDevice,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Customer device retrieval failed: {error}', [
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Gagal mengambil data perangkat dari GenieACS.',
            ], 502);
        }
    }

    public function wifi()
    {
        return $this->device();
    }

    public function updateSsid()
    {
        return $this->updateParameter('SSID');
    }

    public function updatePassword()
    {
        return $this->updateParameter('KeyPassphrase');
    }

    private function updateParameter(string $parameter)
    {
        $customer = $this->requireCustomer();

        if (!$customer) {
            return $this->json(['success' => false], 401);
        }

        if ($guard = $this->validateCustomerMapping($customer)) {
            return $guard;
        }

        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $index = (int) ($data['index'] ?? 0);
        $value = trim((string) ($data['value'] ?? ''));

        if ($index < 1 || $index > 4 || $value === '') {
            return $this->json([
                'success' => false,
                'message' => 'Parameter tidak valid.',
            ], 422);
        }

        if (
            $parameter === 'KeyPassphrase' &&
            (strlen($value) < 8 || strlen($value) > 63)
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Password WiFi harus 8-63 karakter.',
            ], 422);
        }

        $deviceId = (string) ($customer['deviceId'] ?? '');

        if ($deviceId === '') {
            return $this->json([
                'success' => false,
                'message' => 'Device belum dipetakan.',
            ], 404);
        }

        $path = "InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$index}.{$parameter}";

        try {
            $this->client()->command(
                $deviceId,
                'setParameterValues',
                [[$path, $value, 'xsd:string']]
            );

            // Jangan simpan nilai SSID/password ke audit log.
            $this->auditCustomer('customer.wifi_parameter_update_requested', $customer, [
                'parameter' => $parameter,
                'index' => $index,
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Perintah konfigurasi dikirim.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'WiFi parameter update failed: {error}', [
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Gagal mengirim perintah konfigurasi ke GenieACS.',
            ], 502);
        }
    }

    public function reboot()
    {
        $customer = $this->requireCustomer();

        if (!$customer) {
            return $this->json(['success' => false], 401);
        }

        if ($guard = $this->validateCustomerMapping($customer)) {
            return $guard;
        }

        $deviceId = (string) ($customer['deviceId'] ?? '');

        if ($deviceId === '') {
            return $this->json([
                'success' => false,
                'message' => 'Device belum dipetakan.',
            ], 404);
        }

        try {
            $this->client()->command($deviceId, 'reboot');
            $this->auditCustomer('customer.device_reboot_requested', $customer);

            return $this->json([
                'success' => true,
                'message' => 'Perintah restart dikirim.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Device reboot request failed: {error}', [
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Gagal mengirim perintah reboot ke GenieACS.',
            ], 502);
        }
    }
}
