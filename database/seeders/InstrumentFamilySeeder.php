<?php

namespace Database\Seeders;

use App\Models\InstrumentFamily;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstrumentFamilySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instrumentFamilies = [
            ['name' => 'Conductor'],
            ['name' => 'Bb Clarinets'],
            ['name' => 'Double Reeds'],
            ['name' => 'Eb, Alto, and Bass Clarinets'],
            ['name' => 'Flutes'],
            ['name' => 'French Horns'],
            ['name' => 'Lower Brass'],
            ['name' => 'Percussion'],
            ['name' => 'Saxophones'],
            ['name' => 'Trumpets'],
        ];

        InstrumentFamily::insert($instrumentFamilies);
    }
}