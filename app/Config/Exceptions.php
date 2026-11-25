<?php
namespace Config;
use CodeIgniter\Config\Exceptions as BaseExceptions;
class Exceptions extends BaseExceptions {
 public int $logLevel=4;
 public string $errorViewPath=APPPATH.'Views/errors';
}
