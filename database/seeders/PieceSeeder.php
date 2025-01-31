<?php

namespace Database\Seeders;

use App\Models\Part;
use App\Models\Piece;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PieceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pieces = Piece::factory(1)->create();

        $parts = Part::all();

        foreach($pieces as $piece) {
            foreach ($parts as $part) {
                $piece->attach($part);
            }
        }
    }
}