<?php

namespace Database\Factories;

use App\Models\Composer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Composer>
 */
class ComposerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ];
    }
}
