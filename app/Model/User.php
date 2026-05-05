<?php

declare(strict_types=1);

namespace App\Model;

class User
{
    public int $id;
    public string $username;
    public string $password;
    public int $roleId;
    public ?string $roleName = null;

    public function __construct(array $data)
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->username = (string) ($data['username'] ?? '');
        $this->password = (string) ($data['password'] ?? '');
        $this->roleId = (int) ($data['role_id'] ?? ($data['roleId'] ?? 0));
        $this->roleName = $data['role_name'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'role_name' => $this->roleName,
        ];
    }
}
