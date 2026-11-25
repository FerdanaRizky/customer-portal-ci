<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PortalModel;

class SystemController extends BaseController
{
    public function ping() { return $this->json(['success' => true, 'service' => 'customer-portal-ci']); }

    public function config()
    {
        $settings = new PortalModel();
        return $this->json([
            'appName' => $settings->setting('app_name', 'Pesona Wifi'),
            'logoUrl' => $settings->setting('logo_url'),
        ]);
    }
}
