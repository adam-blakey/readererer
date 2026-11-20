<?php

namespace Database\Factories;

use App\Models\DressCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DressCode>
 */
class DressCodeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->colorName()).' concert dress',
            'description' => $this->faker->sentence(),
        ];
    }
}
