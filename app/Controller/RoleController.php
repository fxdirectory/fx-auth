<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Role;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class RoleController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function list(Request $request, Response $response): Response
    {
        $stmt = $this->pdo->query('SELECT id, name, description FROM roles ORDER BY id ASC');
        $roles = array_map(fn(array $row) => (new Role($row))->toArray(), $stmt->fetchAll(PDO::FETCH_ASSOC));

        return $this->json($response, ['data' => $roles]);
    }

    public function view(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return $this->json($response, ['error' => 'Id role tidak valid'], 400);
        }

        $stmt = $this->pdo->prepare('SELECT id, name, description FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data === false) {
            return $this->json($response, ['error' => 'Role tidak ditemukan'], 404);
        }

        return $this->json($response, ['data' => (new Role($data))->toArray()]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
