<?php

declare(strict_types=1);

namespace App\Functions;

use App\Conf\Database;
use App\Utils\ApiResponse;
use App\Utils\AuthUtils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middle\ValidateInputMiddleware;
use PDO;

class AuthFunction
{
    private PDO $pdo;
    private AuthUtils $authUtils;
    private validateInputMiddleware $validateInput;

    public function __construct()
    {
        $this->pdo = Database::connect();
        $this->authUtils = new AuthUtils();
        $this->validateInput = new ValidateInputMiddleware();
    }

    public function register(Request $request, Response $response): Response
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];

        $rules = [
            'name'      => ['required' => true, 'type' => 'string'],
            'username'  => ['required' => true, 'type' => 'username'],
            'password'  => ['required' => true, 'type' => 'password'],
        ];

        $errors = $this->validateInput->validate($data, $rules);
        if (!empty($errors)) {
            return ApiResponse::error($response, 'Validasi Gagal', $errors, 400);
        }
        
        $name = trim((string) ($data['name'] ?? ''));
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');


        $roleId = $this->authUtils->findRoleIdByName('user');
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

        $rules = [
            'username' => ['required' => true, 'type' => 'username'],
            'password' => ['required' => true, 'type' => 'password'],
        ];

        $errors = $this->validateInput->validate($data, $rules);
        if (!empty($errors)) {
            return ApiResponse::error($response, 'Validasi Gagal', $errors, 400);
        }

        $username = trim((string) ($data['username'] ?? ''));
        $password = md5((string) ($data['password'] ?? ''));

        $user = $this->authUtils->findUserByUsername($username);
        if ($user === null || $password !== $user['password']) {
            return ApiResponse::unauthorized($response, 'Username atau password tidak valid');
        }

        $accessToken = $this->authUtils->generateAccessToken($user);
        $refreshToken = $this->authUtils->createRefreshToken((int) $user['id']);

        return ApiResponse::success(
            $response, 
            'Login berhasil', 
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => $this->authUtils->getJwtExpire(),
                'token_type' => 'Bearer',
            ]
        );
    }

    public function logout(Request $request, Response $response): Response
    {
        $user_data = $request->getAttribute('user');

        if ($user_data === null) {
            return ApiResponse::unauthorized($response, 'User tidak ditemukan di token');
        }

        $userId = (int) ($user_data->sub ?? 0);
        if ($userId <= 0) {
            return ApiResponse::unauthorized($response, 'User tidak valid');
        }

        $this->authUtils->revokeRefreshTokenByUserId($userId);

        return ApiResponse::success($response, 'Logout berhasil');
    }

    public function profile(Request $request, Response $response): Response
    {
        $user_data = $request->getAttribute('user');
        
        if ($user_data === null) {
            return ApiResponse::unauthorized($response, 'User tidak ditemukan di token');
        }

        $userId = (int) ($user_data->sub ?? 0);
        $user = $this->authUtils->findUserById($userId);
        
        if ($user === null) {
            return ApiResponse::notFound($response, 'User tidak ditemukan');
        }

        return ApiResponse::success(
            $response, 
            'Profile berhasil diambil', 
            [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'role' => $user['role_name'],
            ]);
    }

    public function refresh(Request $request, Response $response): Response
    {
        $user_data = $request->getAttribute('user');

        if ($user_data === null) {
            return ApiResponse::unauthorized($response, 'User tidak ditemukan di token');
        }

        $userId = (int) ($user_data->sub ?? 0);
        if ($userId <= 0) {
            return ApiResponse::unauthorized($response, 'User tidak valid');
        }

        $user = $this->authUtils->findUserById($userId);
        if ($user === null) {
            return ApiResponse::notFound($response, 'User tidak ditemukan');
        }

        $this->authUtils->revokeRefreshTokenByUserId($userId);
        $accessToken = $this->authUtils->generateAccessToken($user);
        $newRefreshToken = $this->authUtils->createRefreshToken($userId);

        return ApiResponse::success($response, 'Token berhasil diperbarui', [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => $this->authUtils->getJwtExpire(),
            'token_type' => 'Bearer',
        ]);
    }

    
}