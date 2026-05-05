<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class UserSeeder extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $existingRoles = $this->fetchAll("SELECT id, name FROM roles WHERE name IN ('user','superadmin')");
        $roleIds = [];
        foreach ($existingRoles as $role) {
            $roleIds[$role['name']] = (int) $role['id'];
        }

        $rolesToInsert = [];
        if (!isset($roleIds['user'])) {
            $rolesToInsert[] = [
                'name' => 'user',
                'description' => 'Default user role',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!isset($roleIds['superadmin'])) {
            $rolesToInsert[] = [
                'name' => 'superadmin',
                'description' => 'Administrator role',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rolesToInsert)) {
            $this->table('roles')->insert($rolesToInsert)->saveData();
            $existingRoles = $this->fetchAll("SELECT id, name FROM roles WHERE name IN ('user','superadmin')");
            $roleIds = [];
            foreach ($existingRoles as $role) {
                $roleIds[$role['name']] = (int) $role['id'];
            }
        }

        if (empty($roleIds['superadmin']) || empty($roleIds['user'])) {
            throw new RuntimeException('Role IDs for superadmin or user could not be resolved.');
        }

        $usersToInsert = [];
        $existingUsers = $this->fetchAll("SELECT username FROM users WHERE username IN ('admin', 'user')");
        $existingUserUsernames = array_column($existingUsers, 'username');

        if (!in_array('superadmin', $existingUserUsernames, true)) {
            $usersToInsert[] = [
                'role_id' => $roleIds['superadmin'],
                'username' => 'fxdirectory',
                'password' => md5('Mustafi21'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!in_array('user', $existingUserUsernames, true)) {
            $usersToInsert[] = [
                'role_id' => $roleIds['user'],
                'username' => 'user',
                'password' => md5('password123'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($usersToInsert)) {
            $this->table('users')->insert($usersToInsert)->saveData();
        }
    }
}
