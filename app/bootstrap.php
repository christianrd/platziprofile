<?php

require '../vendor/autoload.php';

// Config settings
$config['displayErrorDetails'] = true;

// Create and configure Slim app
$app = new \Slim\App(["settings" => $config]);

$container = $app->getContainer();

$container['Hola'] = function($container) {
    return new \App\Controllers\Hola($container->client);
};

$container['client'] = function($container) {
    return new \Goutte\Client();
};

require 'routes.php';
