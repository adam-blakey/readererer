<?php

namespace Database\Seeders;

use App\Models\InstrumentFamily;
use Illuminate\Database\Seeder;

class InstrumentFamilySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instrumentFamilies = [
            ['name' => 'Conductor', 'color' => 'blue'],
            ['name' => 'Bb Clarinets', 'color' => 'azure'],
            ['name' => 'Double Reeds', 'color' => 'indigo'],
            ['name' => 'Eb, Alto, and Bass Clarinets', 'color' => 'purple'],
            ['name' => 'Flutes', 'color' => 'pink'],
            ['name' => 'French Horns', 'color' => 'red'],
            ['name' => 'Lower Brass', 'color' => 'orange'],
            ['name' => 'Percussion', 'color' => 'yellow'],
            ['name' => 'Saxophones', 'color' => 'lime'],
            ['name' => 'Trumpets', 'color' => 'green'],
        ];

        InstrumentFamily::insert($instrumentFamilies);
    }
}
