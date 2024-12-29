<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ensemble;

class EnsembleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ensemble = Ensemble::factory(10)->create();

        $ensemble->each(function ($ensemble) {
            $no_admins = rand(1, 3);
            $admins = range(1, 10);
            shuffle($admins);

            for ($i = 0; $i < $no_admins; $i++) {
                $ensemble->admins()->attach(array_pop($admins));
            }
        });
    }
}