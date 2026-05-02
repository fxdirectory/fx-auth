<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class UserSeeder extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $existingRoles = $this->fetchAll("SELECT id, name FROM roles WHERE name IN ('user','admin')");
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
        if (!isset($roleIds['admin'])) {
            $rolesToInsert[] = [
                'name' => 'admin',
                'description' => 'Administrator role',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rolesToInsert)) {
            $this->table('roles')->insert($rolesToInsert)->saveData();
            $existingRoles = $this->fetchAll("SELECT id, name FROM roles WHERE name IN ('user','admin')");
            $roleIds = [];
            foreach ($existingRoles as $role) {
                $roleIds[$role['name']] = (int) $role['id'];
            }
        }

        if (empty($roleIds['admin']) || empty($roleIds['user'])) {
            throw new RuntimeException('Role IDs for admin or user could not be resolved.');
        }

        $usersToInsert = [];
        $existingUsers = $this->fetchAll("SELECT email FROM users WHERE email IN ('admin@example.com', 'user@example.com')");
        $existingUserEmails = array_column($existingUsers, 'email');

        if (!in_array('admin@example.com', $existingUserEmails, true)) {
            $usersToInsert[] = [
                'role_id' => $roleIds['admin'],
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!in_array('user@example.com', $existingUserEmails, true)) {
            $usersToInsert[] = [
                'role_id' => $roleIds['user'],
                'name' => 'User',
                'email' => 'user@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($usersToInsert)) {
            $this->table('users')->insert($usersToInsert)->saveData();
        }
    }
}
