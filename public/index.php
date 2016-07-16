<?php

require '../vendor/autoload.php';

// Create and configure Slim app
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

// Define app routes
$app->get('/', function($request, $response, $args) {
    $uri = $request->getUri();
    $response->getBody()->write("Usage: {$uri}montalvomiguelo");
    return $response;
});

$app->get('/{name}', function($request, $response, $args) {
    $name = $args['name'];
    $this->logger->addInfo("Get $name's Platzi profile");
    $platziProfileRepository = new \App\PlatziProfileRepository($this->client);

    $profileData = $platziProfileRepository->find($name);

    if (!$profileData) {
        $notFoundHandler = $this->notFoundHandler;
        return $notFoundHandler($request, $response);
    }

    return $response->withJson($profileData);
});

// Run app
$app->run();
