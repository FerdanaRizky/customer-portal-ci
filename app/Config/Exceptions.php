<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Exception and error display configuration.
 */
class Exceptions extends BaseConfig
{
    public bool $log = true;

    public array $ignoreCodes = [404];

    public string $errorViewPath = APPPATH . 'Views/errors';
}
