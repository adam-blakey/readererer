<?php

namespace Database\Factories;

use App\Enums\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SetupGroup>
 */
class SetupGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $week = $this->faker->numberBetween(1, 4);

        return [
            'name' => 'Setup group '.$week,
            'week' => $week,
            'color' => $this->faker->randomElement(Color::cases()),
        ];
    }
}
