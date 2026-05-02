<?php

declare(strict_types=1);

namespace App\Model;

class User
{
    public int $id;
    public string $name;
    public string $email;
    public string $password;
    public int $roleId;
    public ?string $roleName = null;

    public function __construct(array $data)
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->name = (string) ($data['name'] ?? '');
        $this->email = (string) ($data['email'] ?? '');
        $this->password = (string) ($data['password'] ?? '');
        $this->roleId = (int) ($data['role_id'] ?? ($data['roleId'] ?? 0));
        $this->roleName = $data['role_name'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->roleId,
            'role_name' => $this->roleName,
        ];
    }
}
