<?php

require '../vendor/autoload.php';

// Config settings
$config['displayErrorDetails'] = true;

// Create and configure Slim app
$app = new \Slim\App(["settings" => $config]);

$container = $app->getContainer();

$container['client'] = function($container) {
    return new \Goutte\Client();
};

// Define app routes
$app->get('/', function($request, $response, $args) {
    $uri = $request->getUri();
    $response->getBody()->write("Usage: {$uri}montalvomiguelo/");
    return $response;
});

$app->get('/{name}', function($request, $response, $args) {
    $name = $args['name'];
    $platziProfileRepository = new \App\PlatziProfileRepository($this->client);

    $profileData = $platziProfileRepository->find($name);

    if (!$profileData) {
        $notFoundHandler = $this->notFoundHandler;
        return $notFoundHandler($request, $response);
    } else {
        return $response->withJson($profileData);
    }

});

// Run app
$app->run();
