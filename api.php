<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$stream = new StreamHandler(__DIR__ . '/logs/influx_api.log', Logger::DEBUG);
$logger = new Logger('influxApiLogger');
$logger->pushHandler($stream);
$logger->info('Api requested');