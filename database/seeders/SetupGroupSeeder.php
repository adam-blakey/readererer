<?php

namespace Database\Seeders;

use App\Models\SetupGroup;
use Illuminate\Database\Seeder;

class SetupGroupSeeder extends Seeder
{
    public function run(): void
    {
        $colors = ['purple', 'yellow', 'azure', 'teal'];
        for ($i = 1; $i <= 4; $i++) {
            $setupGroup = SetupGroup::factory()->create([
                'name' => 'Setup group '.$i,
                'week' => $i,
                'color' => $colors[$i - 1],
            ]);
        }
    }
}
