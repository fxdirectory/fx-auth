<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\RoleController;
use App\Config\Database;
use App\Config\JWTConfig;
use App\Helper\ApiResponse;
use App\Middleware\JWTMiddleware;
use App\Middleware\ValidateInputMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;

return function (App $app): void {
    $pdo = Database::connect();

    $auth = new AuthController();
    $role = new RoleController($pdo);

    // Middleware instances
    $jwtMiddleware = new JWTMiddleware();
    
    $registerValidation = new ValidateInputMiddleware([
        'name' => ['required' => true, 'type' => 'string', 'min_length' => 3],
        'email' => ['required' => true, 'type' => 'email'],
        'password' => ['required' => true, 'type' => 'password', 'min_length' => 6],
    ]);

    $loginValidation = new ValidateInputMiddleware([
        'email' => ['required' => true, 'type' => 'email'],
        'password' => ['required' => true, 'type' => 'password'],
    ]);

    $logoutValidation = new ValidateInputMiddleware([
        'refresh_token' => ['required' => true, 'type' => 'string'],
    ]);

    $refreshValidation = new ValidateInputMiddleware([
        'refresh_token' => ['required' => true, 'type' => 'string'],
    ]);

    $app->get(
        '/', 
        function (Request $request, Response $response): Response {
            return ApiResponse::success($response, 'fx-auth is running');
    });

    // Auth routes
    $app->post('/auth/register', [$auth, 'register']);
    $app->post('/auth/login', [$auth, 'login']);
    $app->post('/auth/logout', [$auth, 'logout']);
    $app->post('/auth/refresh', [$auth, 'refresh']);

    $app->get('/auth/profile', [$auth, 'profile'])
        ->add($jwtMiddleware);

    // Role routes with JWT protection
    $app->get('/roles', [$role, 'list'])
        ->add($jwtMiddleware);

    $app->get('/roles/{id:[0-9]+}', [$role, 'view'])
        ->add($jwtMiddleware);
};
