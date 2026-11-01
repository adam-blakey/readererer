<?php

namespace Database\Factories;

use App\Enums\Color;
use App\Models\InstrumentFamily;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstrumentFamily>
 */
class InstrumentFamilyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->word()).'s',
            'color' => $this->faker->randomElement(Color::cases()),
        ];
    }
}
