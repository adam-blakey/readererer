<?php

namespace Database\Factories;

use App\Models\Ensemble;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ensemble>
 */
class EnsembleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->uniqueName(),
            'slug' => fn (array $attributes) => Str::slug($attributes['name']),
            'image' => $this->faker->imageUrl(),
            'show' => $this->faker->boolean(),
            'seating_plan_enabled' => $this->faker->boolean(),
        ];
    }

    /**
     * Generate a name whose slug no existing ensemble has, without relying on Faker's bounded unique pool.
     */
    protected function uniqueName(): string
    {
        do {
            $name = ucfirst($this->faker->words(2, true));
        } while (Ensemble::withTrashed()->where('slug', Str::slug($name))->exists());

        return $name;
    }
}
