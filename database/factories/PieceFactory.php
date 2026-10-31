<?php

namespace Database\Factories;

use App\Models\Composer;
use App\Models\Piece;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Piece>
 */
class PieceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->word),
            'composer_id' => Composer::factory(),
        ];
    }
}
