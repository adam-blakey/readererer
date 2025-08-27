<?php

namespace Database\Seeders;

use App\Models\SetupGroup;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SetupGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = ['purple', 'yellow', 'azure', 'teal'];
        for ($i = 1; $i <= 4; $i++)
        {
            $setupGroup = SetupGroup::factory()->create([
                'name' => $i,
                'week' => $i,
                'color' => $colors[$i-1],
            ]);
        }
    }
}
