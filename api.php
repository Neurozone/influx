<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$stream = new StreamHandler(__DIR__ . '/logs/influx_api.log', Logger::DEBUG);
$logger = new Logger('influxApiLogger');
$logger->pushHandler($stream);
$logger->info('Api requested');
$stream = new StreamHandler(__DIR__ . '/logs/influx.log', Logger::DEBUG);
$logger = new Logger('influxLogger');
$logger->pushHandler($stream);
$logger->info('Influx started');

$loader = new \Twig\Loader\FilesystemLoader($templatePath);
$twig = new \Twig\Environment($loader, [__DIR__ . '/cache' => 'cache', 'debug' => true,]);
$twig->addExtension(new Umpirsky\Twig\Extension\PhpFunctionExtension());
$twig->addExtension(new \Twig\Extension\DebugExtension());

// Create Router instance
$router = new \Bramus\Router\Router();

// @todo API resful

$router->mount('/api', function () use ($router, $db, $logger) {

    $router->get('/feed', function() { /* ... */ });

    $router->get('/feed/{id}/unread', function() {

    });

    $router->get('/folder', function() { /* ... */ });
    $router->get('/folder/{id}', function() { /* ... */ });
    $router->post('pattern', function() { /* ... */ });
    $router->put('pattern', function() { /* ... */ });
    $router->delete('pattern', function() { /* ... */ });
    $router->options('pattern', function() { /* ... */ });
    $router->patch('pattern', function() { /* ... */ });

});

$router->run();