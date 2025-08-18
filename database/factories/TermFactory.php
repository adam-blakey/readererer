<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Spring', 'Summer', 'Autumn']).' '.$this->faker->randomElement(['2023', '2024', '2025']),
            'slug' => fn(array $attributes) => Str::slug($attributes['name']),
        ];
    }
}
