<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes = Services::routes();
$routes->get('/', 'PortalController::index');
$routes->get('login', 'PortalController::index');
$routes->get('admin', 'AdminController::index');
$routes->get('dashboard', 'PortalController::dashboard');

$routes->group('api', static function (RouteCollection $routes): void {
    $routes->get('ping', 'Api\\SystemController::ping');
    $routes->get('config', 'Api\\SystemController::config');
    $routes->post('auth/request-otp', 'Api\\AuthController::requestOtp');
    $routes->post('auth/verify-otp', 'Api\\AuthController::verifyOtp');
    $routes->post('auth/logout', 'Api\\AuthController::logout');

    $routes->get('customer/me', 'Api\\CustomerController::me');
    $routes->get('customer/device', 'Api\\CustomerController::device');
    $routes->get('customer/wifi', 'Api\\CustomerController::wifi');
    $routes->post('customer/wifi/ssid', 'Api\\CustomerController::updateSsid');
    $routes->post('customer/wifi/password', 'Api\\CustomerController::updatePassword');
    $routes->post('customer/device/reboot', 'Api\\CustomerController::reboot');

    $routes->post('admin/auth/login', 'Api\\AdminController::login');
    $routes->post('admin/auth/logout', 'Api\\AdminController::logout');
    $routes->get('admin/status', 'Api\\AdminController::status');
    $routes->get('admin/settings', 'Api\\AdminController::settings');
    $routes->put('admin/settings', 'Api\\AdminController::saveSettings');
    $routes->post('admin/logo', 'Api\\AdminController::uploadLogo');
    $routes->delete('admin/logo', 'Api\\AdminController::deleteLogo');
    $routes->put('admin/credentials', 'Api\\AdminController::credentials');
    $routes->get('admin/customers', 'Api\\AdminController::customers');
    $routes->get('admin/logs', 'Api\\AdminController::logs');
    $routes->post('admin/whatsapp/connect', 'Api\\AdminController::whatsappConnect');
    $routes->get('admin/whatsapp/status', 'Api\\AdminController::whatsappStatus');
});
