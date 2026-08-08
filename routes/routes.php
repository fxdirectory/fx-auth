<?php

declare(strict_types=1);

use App\Functions\AuthFunction;
use App\Functions\RoleFunction;
use App\Conf\Database;
use App\Utils\ApiResponse;
use App\Middle\JWTMiddleware;
use App\Middle\ValidateInputMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;
use Slim\Psr7\Request;

return function (App $app): void {
    $pdo = Database::connect();
    $role = new RoleFunction($pdo);

    // Middleware instances
    $jwtMiddleware = new JWTMiddleware();

    //url root redirect to health check
    $app->get('/', function (Request $request, Response $response): Response {
        return $response
            ->withHeader('Location', '/health')
            ->withStatus(302);
    }); 
    
    $app->get('/health', 
        function (Request $request, Response $response): Response {
            return ApiResponse::success($response, 'fx-auth is running');
    });

    // Auth routes
    $app->post('/auth/register',    [new AuthFunction(), 'register']);
    $app->post('/auth/login',       [new AuthFunction(), 'login']);
    $app->post('/auth/logout',      [new AuthFunction(), 'logout'])->add($jwtMiddleware);
    $app->post('/auth/refresh',     [new AuthFunction(), 'refresh'])->add($jwtMiddleware);
    $app->get('/auth/profile',      [new AuthFunction(), 'profile'])->add($jwtMiddleware);

    // Role routes with JWT protection
    $app->get('/roles',             [$role, 'list'])->add($jwtMiddleware);
    $app->get('/roles/{id:[0-9]+}', [$role, 'view'])->add($jwtMiddleware);
};
