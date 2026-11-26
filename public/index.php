<?php

use CodeIgniter\Boot;

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('COMPOSER_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();

require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
