<?php

declare(strict_types=1);

use App\Functions\AuthFunction;
use App\Functions\RoleFunction;
use App\Conf\Database;
use App\Utils\ApiResponse;
use App\Middle\JWTMiddleware;
use App\Middle\ValidateInputMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;

return function (App $app): void {
    $pdo = Database::connect();

    $auth = new AuthFunction();
    $role = new RoleFunction($pdo);

    // Middleware instances
    $jwtMiddleware = new JWTMiddleware();
    
    $registerValidation = new ValidateInputMiddleware([
        'name' => ['required' => true, 'type' => 'string', 'min_length' => 3],
        'username' => ['required' => true, 'type' => 'username'],
        'password' => ['required' => true, 'type' => 'password', 'min_length' => 6],
    ]);

    $loginValidation = new ValidateInputMiddleware([
        'username' => ['required' => true, 'type' => 'username'],
        'password' => ['required' => true, 'type' => 'password'],
    ]);

    $logoutValidation = new ValidateInputMiddleware([
        'refresh_token' => ['required' => true, 'type' => 'string'],
    ]);

    $refreshValidation = new ValidateInputMiddleware([
        'refresh_token' => ['required' => true, 'type' => 'string'],
    ]);

    //url root redirect to health check
    $app->get('/', function (Request $request, Response $response): Response {
        return $response
            ->withHeader('Location', '/health')
            ->withStatus(302);
    }); 
    
    $app->get(
        '/health', 
        function (Request $request, Response $response): Response {
            return ApiResponse::success($response, 'fx-auth is running');
    });

    // Auth routes
    $app->post('/auth/register', [$auth, 'register'])->add($registerValidation);
    $app->post('/auth/login', [$auth, 'login'])->add($loginValidation);
    $app->post('/auth/logout', [$auth, 'logout'])->add($logoutValidation);
    $app->post('/auth/refresh', [$auth, 'refresh'])->add($refreshValidation);

    $app->get('/auth/profile', [$auth, 'profile'])
        ->add($jwtMiddleware);

    // Role routes with JWT protection
    $app->get('/roles', [$role, 'list'])
        ->add($jwtMiddleware);

    $app->get('/roles/{id:[0-9]+}', [$role, 'view'])
        ->add($jwtMiddleware);
};
