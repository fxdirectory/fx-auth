<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\RoleController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {
    $pdo = (require __DIR__ . '/database.php')();

    $auth = new AuthController(
        $pdo,
        $_ENV['JWT_SECRET'] ?? '',
        $_ENV['JWT_ISSUER'] ?? '',
        $_ENV['JWT_AUDIENCE'] ?? '',
        (int) ($_ENV['JWT_EXPIRE'] ?? 3600),
        (int) ($_ENV['JWT_REFRESH_EXPIRE'] ?? 86400)
    );

    $role = new RoleController($pdo);

    $app->get('/', function (Request $request, Response $response): Response {
        $response->getBody()->write('FX Auth Microservice is running');
        return $response;
    });

    $app->post('/auth/register', [$auth, 'register']);
    $app->post('/auth/login', [$auth, 'login']);
    $app->post('/auth/logout', [$auth, 'logout']);
    $app->post('/auth/refresh', [$auth, 'refresh']);
    $app->get('/auth/profile', [$auth, 'profile']);

    $app->get('/roles', [$role, 'list']);
    $app->get('/roles/{id:[0-9]+}', [$role, 'view']);
};
