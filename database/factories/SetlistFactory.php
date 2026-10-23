<?php

namespace Database\Factories;

use App\Models\Setlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setlist>
 */
class SetlistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
        ];
    }
}
