<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Migrations extends BaseConfig
{
    /**
     * Enable or disable migrations.
     */
    public bool $enabled = true;

    /**
     * Table used to track applied migrations.
     */
    public string $table = 'migrations';

    /**
     * Timestamp format used by migration filenames.
     */
    public string $timestampFormat = 'Y-m-d-His_';

    /**
     * Prevent concurrent migration processes.
     */
    public bool $lock = false;
}
