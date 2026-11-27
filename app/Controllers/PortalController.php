<?php
namespace App\Controllers;

class PortalController extends BaseController
{
    private function preventPageCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0');
    }

    public function index()
    {
        $this->preventPageCaching();
        if (session('customer')) return redirect()->to('/dashboard');
        return view('portal/login');
    }

    public function dashboard()
    {
        $this->preventPageCaching();
        if (!session('customer')) return redirect()->to('/');
        return view('portal/dashboard');
    }
}
