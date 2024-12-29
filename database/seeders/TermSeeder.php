<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Term;
use App\Models\TermDate;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $term = Term::factory(10)->create();

        $term->each(function ($term) {
            $term->term_dates()->saveMany(TermDate::factory(5)->make());
        });
    }
}