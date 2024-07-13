<?php

namespace Database\Seeders;

use App\Models\Part;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parts = [
            'Full Score',
            'Piccolo',
            'Flute 1',
            'Flute 2',
            'Oboe 1',
            'Oboe 2',
            'Bassoon 1',
            'Bassoon 2',
            'Contrabassoon',
            'E-flat Clarinet',
            'Clarinet 1',
            'Clarinet 2',
            'Clarinet 3',
            'Alto Clarinet',
            'Bass Clarinet',
            'Contrabass Clarinet',
            'Alto Saxophone 1',
            'Alto Saxophone 2',
            'Tenor Saxophone',
            'Baritone Saxophone',
            'Horn in F 1',
            'Horn in F 2',
            'Horn in F 3',
            'Horn in F 4',
            'Trumpet 1',
            'Trumpet 2',
            'Trumpet 3',
            'Trombone 1',
            'Trombone 2',
            'Trombone 3',
            'Euphonium',
            'Tuba',
            'Timpani',
            'Percussion 1',
            'Percussion 2'
        ];

        foreach ($parts as $part) {
            Part::factory()->create([
                'name' => $part
            ]);
        }
    }
}
