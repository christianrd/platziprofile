<?php

// Define app routes
$app->get('/', 'Hola:index');

$app->get('/{name}', 'Hola:profile');
