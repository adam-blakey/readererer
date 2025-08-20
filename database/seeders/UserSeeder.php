<?php

namespace Database\Seeders;

use App\Models\Ensemble;
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

        // Create a user for each type of user role.
        $roleNames = ['Guest', 'Ensemble', 'Member', 'Moderator', 'Admin'];
        $i = 0;
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'image' => 'https://adam.blakey.family/wp-content/uploads/sites/4/2022/02/Adam-cutaway-4.png.webp',
                'username' => strtolower($roleNames[$i]),
                'email' => $roleNames[$i] . '@example.com',
                'password' => bcrypt('password'),
                'first_name' => $roleNames[$i],
                'last_name' => 'User',
            ]);
            $i++;
        }
    }
}
