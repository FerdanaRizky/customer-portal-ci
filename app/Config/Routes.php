<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes = Services::routes();
$routes->get('/', 'PortalController::index');
$routes->get('login', 'PortalController::index');
$routes->get('admin', 'AdminController::index');
$routes->get('admin/akun', 'AdminController::index');
$routes->get('admin/pengaturan', 'AdminController::index');
$routes->get('admin/status-sistem', 'AdminController::index');
$routes->get('admin/whatsapp', 'AdminController::index');
$routes->get('admin/dashboard', 'AdminController::index');
$routes->get('dashboard', 'PortalController::dashboard');

$routes->group('api', static function (RouteCollection $routes): void {
    $routes->get('ping', 'Api\\SystemController::ping');
    $routes->get('config', 'Api\\SystemController::config');
    $routes->post('auth/request-otp', 'Api\\AuthController::requestOtp');
    $routes->post('auth/verify-otp', 'Api\\AuthController::verifyOtp');
    $routes->post('auth/logout', 'Api\\AuthController::logout');

    $routes->get('customer/me', 'Api\\CustomerController::me');
    $routes->get('customer/portal-config', 'Api\\CustomerController::portalConfig');
    $routes->get('customer/device', 'Api\\CustomerController::device');
    $routes->get('customer/wifi', 'Api\\CustomerController::wifi');
    $routes->post('customer/wifi/ssid', 'Api\\CustomerController::updateSsid');
    $routes->post('customer/wifi/password', 'Api\\CustomerController::updatePassword');
    $routes->post('customer/device/reboot', 'Api\\CustomerController::reboot');

    $routes->post('admin/auth/login', 'Api\\AdminController::login');
    $routes->post('admin/auth/logout', 'Api\\AdminController::logout');
    $routes->get('admin/status', 'Api\\AdminController::status');
    $routes->get('admin/health', 'Api\\AdminController::health');
    $routes->get('admin/monitoring/metrics', 'Api\\AdminController::monitoringMetrics');
    $routes->get('admin/monitoring/application-logs', 'Api\\AdminController::applicationLogs');
    $routes->get('admin/settings', 'Api\\AdminController::settings');
    $routes->put('admin/settings', 'Api\\AdminController::saveSettings');
    $routes->post('admin/logo', 'Api\\AdminController::uploadLogo');
    $routes->delete('admin/logo', 'Api\\AdminController::deleteLogo');
    $routes->put('admin/credentials', 'Api\\AdminController::credentials');
    $routes->get('admin/customers', 'Api\\AdminController::customers');
    $routes->get('admin/genieacs/devices', 'Api\\AdminController::genieacsDevices');
    $routes->get('admin/logs', 'Api\\AdminController::logs');
    $routes->get('admin/otp-delivery-logs', 'Api\\AdminController::otpDeliveryLogs');
    $routes->get('admin/whatsapp/devices', 'Api\\AdminController::whatsappDevices');
    $routes->post('admin/whatsapp/hide', 'Api\\AdminController::whatsappHideDevice');
    $routes->post('admin/whatsapp/restore', 'Api\\AdminController::whatsappRestoreDevice');
    $routes->post('admin/whatsapp/create', 'Api\\AdminController::whatsappCreateDevice');
    $routes->post('admin/whatsapp/select', 'Api\\AdminController::whatsappSelectDevice');
    $routes->get('admin/whatsapp/status', 'Api\\AdminController::whatsappStatus');
    $routes->post('admin/whatsapp/qr', 'Api\\AdminController::whatsappQr');
    $routes->post('admin/whatsapp/pair-code', 'Api\\AdminController::whatsappPairCode');
});
