<?php

declare(strict_types=1);

namespace App\Helper;

use Psr\Http\Message\ResponseInterface as Response;

class ApiResponse
{
    public static function success(Response $response, string $message, array $data = null, int $status = 200): Response
    {
        $payload = [
            'message' => $message,
            'status' => 'success',
            'data' => $data
        ];

        return self::json($response, $payload, $status);
    }

    public static function error(Response $response, string $message, int $status = 400): Response
    {
        $payload = [
            'message' => $message,
            'status' => 'error',
            'data' => null
        ];

        return self::json($response, $payload, $status);
    }

    public static function unauthorized(Response $response, string $message = 'Unauthorized'): Response
    {
        $payload = ['error' => $message];
        return self::json($response, $payload, 401);
    }

    public static function notFound(Response $response, string $message = 'Not Found'): Response
    {
        $payload = [
            'message' => $message,
            'status' => 'error',
            'data' => null
        ];

        return self::json($response, $payload, 404);
    }

    public static function serverError(Response $response, string $message = 'Internal Server Error'): Response
    {
        $payload = [
            'message' => $message,
            'status' => 'error',
            'data' => null
        ];

        return self::json($response, $payload, 500);
    }

    private static function json(Response $response, array $data, int $status = 200): Response
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}