<?php
namespace App\Controllers;

class PortalController extends BaseController
{
    public function index()
    {
        if (session('customer')) return redirect()->to('/dashboard');
        return view('portal/login');
    }

    public function dashboard()
    {
        if (!session('customer')) return redirect()->to('/');
        return view('portal/dashboard');
    }
}
