<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\RefreshToken;
use App\Model\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class AuthController
{
    private PDO $pdo;
    private string $jwtSecret;
    private string $jwtIssuer;
    private string $jwtAudience;
    private int $jwtExpire;
    private int $refreshExpire;

    public function __construct(PDO $pdo, string $jwtSecret, string $jwtIssuer, string $jwtAudience, int $jwtExpire, int $refreshExpire)
    {
        $this->pdo = $pdo;
        $this->jwtSecret = $jwtSecret;
        $this->jwtIssuer = $jwtIssuer;
        $this->jwtAudience = $jwtAudience;
        $this->jwtExpire = $jwtExpire;
        $this->refreshExpire = $refreshExpire;
    }

    public function register(Request $request, Response $response): Response
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            return $this->json($response, ['error' => 'Name, email, dan password wajib diisi'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => 'Email tidak valid'], 400);
        }

        if ($this->findUserByEmail($email) !== null) {
            return $this->json($response, ['error' => 'Email sudah terdaftar'], 409);
        }

        $roleId = $this->findRoleIdByName('user');
        if ($roleId === null) {
            return $this->json($response, ['error' => 'Role default tidak ditemukan'], 500);
        }

        $stmt = $this->pdo->prepare('INSERT INTO users (role_id, name, email, password, created_at, updated_at) VALUES (:role_id, :name, :email, :password, NOW(), NOW())');
        $stmt->execute([
            'role_id' => $roleId,
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return $this->json($response, ['message' => 'Registrasi berhasil'], 201);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];
        $refreshToken = (string) ($data['refresh_token'] ?? '');

        if ($refreshToken === '') {
            return $this->json($response, ['error' => 'Refresh token wajib dikirim'], 400);
        }

        $this->revokeRefreshToken($refreshToken);

        return $this->json($response, ['message' => 'Logout berhasil']);
    }

    public function profile(Request $request, Response $response): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (strpos($authHeader, 'Bearer ') !== 0) {
            return $this->json($response, ['error' => 'Token akses tidak ditemukan'], 401);
        }

        $token = substr($authHeader, 7);
        try {
            $payload = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $userId = (int) ($payload->sub ?? 0);
            $user = $this->findUserById($userId);
            if ($user === null) {
                return $this->json($response, ['error' => 'User tidak ditemukan'], 404);
            }

            return $this->json($response, ['data' => $user->toArray()]);
        } catch (\Exception $e) {
            return $this->json($response, ['error' => 'Token akses tidak valid'], 401);
        }
    }

    public function refresh(Request $request, Response $response): Response
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];
        $refreshToken = (string) ($data['refresh_token'] ?? '');

        if ($refreshToken === '') {
            return $this->json($response, ['error' => 'Refresh token wajib dikirim'], 400);
        }

        $tokenData = $this->findRefreshToken($refreshToken);
        if ($tokenData === null || $tokenData->revoked || $tokenData->isExpired()) {
            return $this->json($response, ['error' => 'Refresh token tidak valid atau kadaluarsa'], 401);
        }

        $user = $this->findUserById($tokenData->userId);
        if ($user === null) {
            return $this->json($response, ['error' => 'User tidak ditemukan'], 404);
        }

        $this->revokeRefreshToken($refreshToken);
        $accessToken = $this->generateAccessToken($user);
        $newRefreshToken = $this->createRefreshToken($user->id);

        return $this->json($response, [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => $this->jwtExpire,
            'token_type' => 'Bearer',
        ]);
    }

    private function findUserByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new User($data) : null;
    }

    private function findUserById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new User($data) : null;
    }

    private function findRoleIdByName(string $name): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? (int) $data['id'] : null;
    }

    private function generateAccessToken(User $user): string
    {
        $now = time();
        $payload = [
            'iss' => $this->jwtIssuer,
            'aud' => $this->jwtAudience,
            'iat' => $now,
            'exp' => $now + $this->jwtExpire,
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->roleName,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function createRefreshToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + $this->refreshExpire);

        $stmt = $this->pdo->prepare('INSERT INTO refresh_tokens (user_id, token_hash, expires_at, revoked, created_at, updated_at) VALUES (:user_id, :token_hash, :expires_at, 0, NOW(), NOW())');
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $hash,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    private function findRefreshToken(string $token): ?RefreshToken
    {
        $hash = hash('sha256', $token);
        $stmt = $this->pdo->prepare('SELECT * FROM refresh_tokens WHERE token_hash = :token_hash LIMIT 1');
        $stmt->execute(['token_hash' => $hash]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new RefreshToken($data) : null;
    }

    private function revokeRefreshToken(string $token): void
    {
        $hash = hash('sha256', $token);
        $stmt = $this->pdo->prepare('UPDATE refresh_tokens SET revoked = 1, updated_at = NOW() WHERE token_hash = :token_hash');
        $stmt->execute(['token_hash' => $hash]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
