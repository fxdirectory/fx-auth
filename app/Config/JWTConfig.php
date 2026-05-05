<?php

declare(strict_types=1);

namespace App\Config;

class JWTConfig
{
    public static function getSecret(): string
    {
        return $_ENV['JWT_SECRET'] ?? '';
    }

    public static function getIssuer(): string
    {
        return $_ENV['JWT_ISSUER'] ?? '';
    }

    public static function getAudience(): string
    {
        return $_ENV['JWT_AUDIENCE'] ?? '';
    }

    public static function getExpire(): int
    {
        return (int) ($_ENV['JWT_EXPIRE'] ?? 3600);
    }

    public static function getRefreshExpire(): int
    {
        return (int) ($_ENV['JWT_REFRESH_EXPIRE'] ?? 86400);
    }
}