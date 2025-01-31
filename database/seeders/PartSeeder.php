<?php

namespace Database\Seeders;

use App\Models\InstrumentFamily;
use App\Models\Part;
use Illuminate\Database\Seeder;

class PartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conductor = InstrumentFamily::where('name', 'Conductor')->first()->id;
        $clarinets = InstrumentFamily::where('name', 'Bb Clarinets')->first()->id;
        $double_reeds = InstrumentFamily::where('name', 'Double Reeds')->first()->id;
        $eb_alto_and_bass_clarinets = InstrumentFamily::where('name', 'Eb, Alto, Bass Clarinet')->first()->id;
        $flutes = InstrumentFamily::where('name', 'Flutes')->first()->id;
        $french_horns = InstrumentFamily::where('name', 'French Horns')->first()->id;
        $lower_brass = InstrumentFamily::where('name', 'Lower Brass')->first()->id;
        $percussion = InstrumentFamily::where('name', 'Percussion')->first()->id;
        $saxophones = InstrumentFamily::where('name', 'Saxophones')->first()->id;
        $trumpets = InstrumentFamily::where('name', 'Trumpets')->first()->id;

        Part::insert(
            [
                'name' => 'Full Score',
                'instrument_family_id' => $conductor
            ],
            [
                'name' => 'Piccolo',
                'instrument_family_id' => $flutes
            ],
            [
                'name' => 'Flute 1',
                'instrument_family_id' => $flutes
            ],
            [
                'name' => 'Flute 2',
                'instrument_family_id' => $flutes
            ],
            [
                'name' => 'Oboe 1',
                'instrument_family_id' => $double_reeds
            ],
            [
                'name' => 'Oboe 2',
                'instrument_family_id' => $double_reeds
            ],
            [
                'name' => 'Bassoon 1',
                'instrument_family_id' => $double_reeds
            ],
            [
                'name' => 'Bassoon 2',
                'instrument_family_id' => $double_reeds
            ],
            [
                'name' => 'Contrabassoon',
                'instrument_family_id' => $double_reeds
            ],
            [
                'name' => 'E-flat Clarinet',
                'instrument_family_id' => $eb_alto_and_bass_clarinets
            ],
            [
                'name' => 'Clarinet 1',
                'instrument_family_id' => $clarinets
            ],
            [
                'name' => 'Clarinet 2',
                'instrument_family_id' => $clarinets
            ],
            [
                'name' => 'Clarinet 3',
                'instrument_family_id' => $clarinets
            ],
            [
                'name' => 'Alto Clarinet',
                'instrument_family_id' => $eb_alto_and_bass_clarinets
            ],
            [
                'name' => 'Bass Clarinet',
                'instrument_family_id' => $eb_alto_and_bass_clarinets
            ],
            [
                'name' => 'Contrabass Clarinet',
                'instrument_family_id' => $eb_alto_and_bass_clarinets
            ],
            [
                'name' => 'Alto Saxophone 1',
                'instrument_family_id' => $saxophones
            ],
            [
                'name' => 'Alto Saxophone 2',
                'instrument_family_id' => $saxophones
            ],
            [
                'name' => 'Tenor Saxophone',
                'instrument_family_id' => $saxophones
            ],
            [
                'name' => 'Baritone Saxophone',
                'instrument_family_id' => $saxophones
            ],
            [
                'name' => 'Horn in F 1',
                'instrument_family_id' => $french_horns
            ],
            [
                'name' => 'Horn in F 2',
                'instrument_family_id' => $french_horns
            ],
            [
                'name' => 'Horn in F 3',
                'instrument_family_id' => $french_horns
            ],
            [
                'name' => 'Horn in F 4',
                'instrument_family_id' => $french_horns
            ],
            [
                'name' => 'Trumpet 1',
                'instrument_family_id' => $trumpets
            ],
            [
                'name' => 'Trumpet 2',
                'instrument_family_id' => $trumpets
            ],
            [
                'name' => 'Trumpet 3',
                'instrument_family_id' => $trumpets
            ],
            [
                'name' => 'Trombone 1',
                'instrument_family_id' => $lower_brass
            ],
            [
                'name' => 'Trombone 2',
                'instrument_family_id' => $lower_brass
            ],
            [
                'name' => 'Trombone 3',
                'instrument_family_id' => $lower_brass
            ],
            [
                'name' => 'Euphonium',
                'instrument_family_id' => $lower_brass
            ],
            [
                'name' => 'Tuba',
                'instrument_family_id' => $lower_brass
            ],
            [
                'name' => 'Timpani',
                'instrument_family_id' => $percussion
            ],
            [
                'name' => 'Percussion 1',
                'instrument_family_id' => $percussion
            ],
            [
                'name' => 'Percussion 2',
                'instrument_family_id' => $percussion
            ]
        );
    }
}