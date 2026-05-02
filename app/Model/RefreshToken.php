<?php

declare(strict_types=1);

namespace App\Model;

class RefreshToken
{
    public int $id;
    public int $userId;
    public string $tokenHash;
    public string $expiresAt;
    public bool $revoked;

    public function __construct(array $data)
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->userId = (int) ($data['user_id'] ?? 0);
        $this->tokenHash = (string) ($data['token_hash'] ?? '');
        $this->expiresAt = (string) ($data['expires_at'] ?? '');
        $this->revoked = (bool) ($data['revoked'] ?? false);
    }

    public function isExpired(): bool
    {
        return strtotime($this->expiresAt) <= time();
    }
}
