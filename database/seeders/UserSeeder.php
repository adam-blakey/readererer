<?php

namespace Database\Seeders;

use App\Models\SetupGroup;
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

        $setup_groups = SetupGroup::all();
        $setup_groups->each(function ($setup_group) use ($users) {
            $no_drivers = rand(1, 3);
            $drivers = $users->random($no_drivers);
            for ($i = 0; $i < $no_drivers; $i++) {
                $setup_group->van_drivers()->attach($drivers[$i], ['sort' => $i+1]);
            }
        });

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
