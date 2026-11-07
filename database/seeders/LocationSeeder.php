<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Coordinates are given so seeding never calls the geocoder.
        Location::insert(collect([
            ['name' => 'St George\'s Bristol', 'address' => 'Great George Street, Bristol BS1 5RR', 'latitude' => 51.4530190, 'longitude' => -2.6045280],
            ['name' => 'Colston Hall', 'address' => 'Trenchard Street, Bristol BS1 5AR', 'latitude' => 51.4545110, 'longitude' => -2.5999920],
            ['name' => 'Redland Parish Church Hall', 'address' => 'Redland Green Road, Bristol BS6 7HE', 'latitude' => 51.4730520, 'longitude' => -2.6047730],
            ['name' => 'Community Centre', 'address' => null, 'latitude' => null, 'longitude' => null],
        ])->map(fn (array $location) => $location + ['created_at' => now(), 'updated_at' => now()])->all());
    }
}
