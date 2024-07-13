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
        $parts = Part::all()->take(20);
        Piece::factory(10)->hasAttached($parts)->create();
    }
}
