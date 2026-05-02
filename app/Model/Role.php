<?php

declare(strict_types=1);

namespace App\Model;

class Role
{
    public int $id;
    public string $name;
    public ?string $description = null;

    public function __construct(array $data)
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->name = (string) ($data['name'] ?? '');
        $this->description = $data['description'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
