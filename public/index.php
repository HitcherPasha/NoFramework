<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/src/bootstrap.php';

$router = new App\Router(
    $app['pdo'],
    $app['smarty'],
);

$router->dispatch(
    $_SERVER['REQUEST_URI'] ?? '/',
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
);
