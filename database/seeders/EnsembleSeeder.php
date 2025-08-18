<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;

class EnsembleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instrument_families = InstrumentFamily::all();

        $ensemble = Ensemble::factory(4)->create();

        $ensemble->each(function ($ensemble) {
            $no_admins = rand(1, 3);
            $admins = range(1, 10);
            shuffle($admins);

            for ($i = 0; $i < $no_admins; $i++) {
                $ensemble->admins()->attach(array_pop($admins));
            }
        });

        $ensemble->each(function ($ensemble) use ($instrument_families) {
            $no_users = rand(1, 10);
            $users = range(1, 10);
            shuffle($users);

            for ($i = 0; $i < $no_users; $i++) {
                $ensemble->users()->attach(array_pop($users), ['instrument_family_id' => $instrument_families->random()->id, 'seat_column' => chr(rand(1, 10)+65), 'seat_row' => rand(1, 10)]);
            }
        });

        $ensemble->each(function ($ensemble) {
            $ensemble_user = User::create([
                'first_name' => $ensemble->name,
                'last_name' => 'Ensemble',
                'email' => $ensemble->slug . '@example.com',
                'password' => bcrypt('password'),
                'role' => UserRole::Ensemble,
            ]);

            $ensemble_user->ensembles()->attach($ensemble);
        });
    }
}
