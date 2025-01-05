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
            'name' => 'Test Admin',
            'email' => 'test-admin@example.com',
            'password' => bcrypt('password'),
            'avatar' => 'https://adam.blakey.family/wp-content/uploads/sites/4/2022/02/Adam-cutaway-4.png.webp',
            'role' => UserRole::Admin,
        ]);

        $users[] = User::create([
            'name' => 'Test Member',
            'email' => 'test-member@example.com',
            'password' => bcrypt('password'),
            'avatar' => 'https://adam.blakey.family/wp-content/uploads/sites/4/2022/02/Adam-cutaway-4.png.webp',
            'role' => UserRole::Member,
        ]);
    }
}