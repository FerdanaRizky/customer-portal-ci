<?php
use CodeIgniter\CLI\CLI;
CLI::error('APPLICATION ERROR');
CLI::write($message);
CLI::write('File: ' . clean_path($exception->getFile()) . ':' . $exception->getLine());
