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
            ->addColumn('username', 'string', ['limit' => 150])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('token', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('token_expires_at', 'datetime', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['username'], ['unique' => true])
            ->addForeignKey('role_id', 'roles', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE'])
            ->create();
    }
}
