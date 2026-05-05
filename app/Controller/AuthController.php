<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Config\JWTConfig;
use App\Helper\ApiResponse;
use App\Model\RefreshToken;
use App\Model\User;
use Firebase\JWT\JWT;
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

    public function __construct()
    {
        $this->pdo = Database::connect();
        $this->jwtSecret = JWTConfig::getSecret();
        $this->jwtIssuer = JWTConfig::getIssuer();
        $this->jwtAudience = JWTConfig::getAudience();
        $this->jwtExpire = JWTConfig::getExpire();
        $this->refreshExpire = JWTConfig::getRefreshExpire();
    }

    public function register(Request $request, Response $response): Response
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];
        
        $name       = $data['name'];
        $username   = $data['username'];
        $password   = $data['password'];

        if ($name === '' || $username === '' || $password === '') {
            return ApiResponse::error(
                $response, 
                'Name, username, dan password wajib diisi'
            );
        }

        $roleId = $this->findRoleIdByName('user');
        if ($roleId === null) {
            return ApiResponse::serverError(
                $response, 
                'Role default tidak ditemukan');
        }

        $stmt = $this->pdo->prepare('
                    INSERT INTO 
                        users (role_id, name, username, password, created_at, updated_at) 
                    VALUES 
                        (:role_id, :name, :username, :password, NOW(), NOW())');
        $stmt->execute([
            'role_id' => $roleId,
            'name' => $name,
            'username' => $username,
            'password' => md5($password),
        ]);

        return ApiResponse::success(
            $response, 
            'Registrasi berhasil', 
            null
        );
    }

    public function login(Request $request, Response $response): Response
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];
        $username = trim((string) ($data['username'] ?? ''));
        $password = md5($data['password']);

        $user = $this->findUserByUsername($username);
        if ($user === null || $password !== $user->password) {
            return ApiResponse::unauthorized($response, 'Username atau password tidak valid');
        }

        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->createRefreshToken($user->id);

        return ApiResponse::success(
            $response, 
            'Login berhasil', 
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => $this->jwtExpire,
                'token_type' => 'Bearer',
            ]
        );
    }

    public function logout(Request $request, Response $response): Response
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];
        $refreshToken = (string) ($data['refresh_token'] ?? '');

        if ($refreshToken === '') {
            return ApiResponse::error($response, 'Refresh token wajib dikirim', 400);
        }

        $this->revokeRefreshToken($refreshToken);

        return ApiResponse::success($response, 'Logout berhasil');
    }

    public function profile(Request $request, Response $response): Response
    {
        $user_data = $request->getAttribute('user');
        
        if ($user_data === null) {
            return ApiResponse::unauthorized($response, 'User tidak ditemukan di token');
        }

        $userId = (int) ($user_data->sub ?? 0);
        $user = $this->findUserById($userId);
        
        if ($user === null) {
            return ApiResponse::notFound($response, 'User tidak ditemukan');
        }

        return ApiResponse::success(
            $response, 
            'Profile berhasil diambil', 
            [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->roleName,
            ]);
    }

    public function refresh(Request $request, Response $response): Response
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];
        $refreshToken = (string) ($data['refresh_token'] ?? '');

        if ($refreshToken === '') {
            return ApiResponse::error($response, 'Refresh token wajib dikirim', 400);
        }

        $tokenData = $this->findRefreshToken($refreshToken);
        if ($tokenData === null || $tokenData->revoked || $tokenData->isExpired()) {
            return ApiResponse::unauthorized($response, 'Refresh token tidak valid atau kadaluarsa');
        }

        $user = $this->findUserById($tokenData->userId);
        if ($user === null) {
            return ApiResponse::notFound($response, 'User tidak ditemukan');
        }

        $this->revokeRefreshToken($refreshToken);
        $accessToken = $this->generateAccessToken($user);
        $newRefreshToken = $this->createRefreshToken($user->id);

        return ApiResponse::success($response, 'Token berhasil diperbarui', [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => $this->jwtExpire,
            'token_type' => 'Bearer',
        ]);
    }

    private function findUserByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare(
                    'SELECT 
                        u.id AS id,
                        u.username AS username, 
                        r.name AS role_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    WHERE u.username = :username 
                    LIMIT 1');
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new User($data) : null;
    }

    private function findUserById(int $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT 
                u.*, 
                r.name AS role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.id = :id 
            LIMIT 1'
        );
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
            'name' => $user->username,
            'role' => $user->roleName,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function createRefreshToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + $this->refreshExpire);

        $stmt = $this->pdo->prepare('UPDATE users SET token = :token_hash, token_expires_at = :expires_at WHERE id = :user_id');
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
}
