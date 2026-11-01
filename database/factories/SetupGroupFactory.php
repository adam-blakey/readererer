<?php

namespace Database\Factories;

use App\Enums\Color;
use App\Models\SetupGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SetupGroup>
 */
class SetupGroupFactory extends Factory
{
    /** @return array<string, mixed> */
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
