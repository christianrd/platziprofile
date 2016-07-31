<?php

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
