<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserRole;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory(10)->create();

        $users[] = User::create([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'username' => 'admin',
            'email' => 'test-admin@example.com',
            'password' => bcrypt('password'),
            'image' => 'https://adam.blakey.family/wp-content/uploads/sites/4/2022/02/Adam-cutaway-4.png.webp',
            'role' => UserRole::Admin,
        ]);

        $users[] = User::create([
            'first_name' => 'Test',
            'last_name' => 'Member',
            'username' => 'member',
            'email' => 'test-member@example.com',
            'password' => bcrypt('password'),
            'image' => 'https://adam.blakey.family/wp-content/uploads/sites/4/2022/02/Adam-cutaway-4.png.webp',
            'role' => UserRole::Member,
        ]);
    }
}
