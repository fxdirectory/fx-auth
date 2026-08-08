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
    // Middleware instances
    $jwtMiddleware = new JWTMiddleware();

    //url root redirect to health check
    $app->get('/auth', function (Request $request, Response $response): Response {
        return $response
            ->withHeader('Location', '/auth/health')
            ->withStatus(302);
    }); 
    
    $app->get('/auth/health', 
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
    $app->get('/auth/roles',             [new RoleFunction(), 'list'])->add($jwtMiddleware);
    $app->get('/auth/roles/{id:[0-9]+}', [new RoleFunction(), 'view'])->add($jwtMiddleware);
};
