<?php

namespace Database\Seeders;

use App\Models\Piece;
use App\Models\Setlist;
use Illuminate\Database\Seeder;

class SetlistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pieces = Piece::all();

        $setlists = Setlist::factory(1)->create();

        foreach ($setlists as $setlist) {
            $no_pieces = rand(1, 6);
            $pieces_to_add = $pieces->random($no_pieces);
            $setlist->pieces()->attach($pieces_to_add);
        }
    }
}
