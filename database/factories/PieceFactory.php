<?php

namespace Database\Factories;

use App\Models\Composer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Piece>
 */
class PieceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'catalogue_id' => $this->faker->numberBetween(1, 800),
            'name' => ucfirst($this->faker->word),
            'composer_id' => Composer::factory()
        ];
    }
}
