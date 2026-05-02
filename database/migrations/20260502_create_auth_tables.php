<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAuthTables extends AbstractMigration
{
    public function change(): void
    {
        $roles = $this->table('roles');
        $roles
            ->addColumn('name', 'string', ['limit' => 50])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['name'], ['unique' => true])
            ->create();

        $users = $this->table('users');
        $users
            ->addColumn('role_id', 'integer', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('email', 'string', ['limit' => 150])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addTimestamps()
            ->addIndex(['email'], ['unique' => true])
            ->addForeignKey('role_id', 'roles', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE'])
            ->create();

        $refreshTokens = $this->table('refresh_tokens');
        $refreshTokens
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('token_hash', 'string', ['limit' => 128])
            ->addColumn('expires_at', 'datetime')
            ->addColumn('revoked', 'boolean', ['default' => false])
            ->addTimestamps()
            ->addIndex(['token_hash'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

    }
}
