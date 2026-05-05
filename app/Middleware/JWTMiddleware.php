<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\JWTConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JWTMiddleware implements MiddlewareInterface
{
    private string $jwtSecret;

    public function __construct()
    {
        $this->jwtSecret = JWTConfig::getSecret();
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorized('Authorization header tidak ditemukan');
        }

        if (strpos($authHeader, 'Bearer ') !== 0) {
            return $this->unauthorized('Format token tidak valid. Gunakan "Bearer <token>"');
        }

        $token = substr($authHeader, 7);

        try {
            $payload = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $request = $request->withAttribute('user', $payload);
            return $handler->handle($request);
        } catch (\Exception $e) {
            return $this->unauthorized('Token tidak valid atau kadaluarsa: ' . $e->getMessage());
        }
    }

    private function unauthorized(string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        $payload = json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
