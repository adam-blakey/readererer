<?php

namespace Database\Factories;

use App\Models\InstrumentFamily;
use App\Models\Piece;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Part>
 */
class PartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $instrument_family = InstrumentFamily::inRandomOrder()->first() ?? InstrumentFamily::factory()->create();

        return [
            'piece_id' => Piece::factory(),
            'instrument_family_id' => $instrument_family->id,
            'name' => $instrument_family->name.' '.$this->faker->numberBetween(1, 4),
        ];
    }
}
