<?php

declare(strict_types=1);

namespace App\Functions;

use App\Utils\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Conf\Database;
use PDO;

class RoleFunction
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function list(Request $request, Response $response): Response
    {
        $stmt = $this->pdo->query('SELECT id, name, description FROM roles ORDER BY id ASC');
        $roles = array_map(fn(array $row) => [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));

        return ApiResponse::success($response, 'Roles berhasil diambil', $roles);
    }

    public function view(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return ApiResponse::error($response, 'Id role tidak valid', 400);
        }

        $stmt = $this->pdo->prepare('SELECT id, name, description FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data === false) {
            return ApiResponse::notFound($response, 'Role tidak ditemukan');
        }

        return ApiResponse::success($response, 'Role berhasil diambil', [
            'id' => (int) $data['id'],
            'name' => $data['name'],
            'description' => $data['description'],
        ]);
    }
}
