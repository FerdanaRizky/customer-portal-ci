<?php
namespace App\Controllers;

use CodeIgniter\Controller;

abstract class BaseController extends Controller
{
    protected function json(array $data, int $status = 200)
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setStatusCode($status)
            ->setJSON($data);
    }

    protected function requireCustomer(): ?array
    {
        $customer = session('customer');
        return is_array($customer) ? $customer : null;
    }

    protected function requireAdmin(): ?array
    {
        $admin = session('admin');
        return is_array($admin) ? $admin : null;
    }
}
