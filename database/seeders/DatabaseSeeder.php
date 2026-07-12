<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SetupGroupSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(InstrumentFamilySeeder::class);
        $this->call(EnsembleSeeder::class);
        $this->call(PieceSeeder::class);
        $this->call(SetlistSeeder::class);
        $this->call(TermSeeder::class);
        $this->call(AttendanceSeeder::class);
        $this->call(EmailLogSeeder::class);
    }
}
