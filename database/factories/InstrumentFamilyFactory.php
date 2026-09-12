<?php

namespace Database\Factories;

use App\Enums\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InstrumentFamily>
 */
class InstrumentFamilyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->word()).'s',
            'color' => $this->faker->randomElement(Color::cases()),
        ];
    }
}
