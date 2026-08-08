<?php
declare(strict_types=1);

namespace App\Utils;

use PDO;
use App\Conf\JWTConfig;
use App\Conf\Database;
use Firebase\JWT\JWT;

class AuthUtils{
    private PDO $pdo;
    private string $jwtSecret;
    private string $jwtIssuer;
    private string $jwtAudience;
    private int $jwtExpire;
    private int $refreshExpire;

    public function __construct(){
        $this->pdo = Database::connect();
        $this->jwtSecret = JWTConfig::getSecret();
        $this->jwtIssuer = JWTConfig::getIssuer();
        $this->jwtAudience = JWTConfig::getAudience();
        $this->jwtExpire = JWTConfig::getExpire();
        $this->refreshExpire = JWTConfig::getRefreshExpire();
    }

    public function getJwtExpire(): int
    {
        return $this->jwtExpire;
    }
    public function findUserByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
                    'SELECT 
                        u.id AS id,
                        u.password AS password,
                        u.username AS username, 
                        r.name AS role_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    WHERE u.username = :username 
                    LIMIT 1');
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function findUserById(int $id): ?array
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
        return $data ?: null;
    }

    public function findRoleIdByName(string $name): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? (int) $data['id'] : null;
    }

    public function generateAccessToken(array $user): string
    {
        $now = time();
        $payload = [
            'iss' => $this->jwtIssuer,
            'aud' => $this->jwtAudience,
            'iat' => $now,
            'exp' => $now + $this->jwtExpire,
            'sub' => (int) $user['id'],
            'name' => $user['username'],
            'role' => $user['role_name'],
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function createRefreshToken(int $userId): string
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

    public function revokeRefreshTokenByUserId(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET token = NULL, token_expires_at = NULL, updated_at = NOW() WHERE id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}