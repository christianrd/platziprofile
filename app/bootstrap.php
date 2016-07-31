<?php

require_once '../vendor/autoload.php';

$app = new \Slim\App;

$container = $app->getContainer();

$container['client'] = function($container) {
    return new \Goutte\Client();
};

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler("php://stderr");
    $logger->pushHandler($file_handler);
    return $logger;
};

require_once 'routes.php';
