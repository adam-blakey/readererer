<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use DateInterval;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TermDate>
 */
class TermDateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'start_datetime' => Carbon::parse($this->faker->dateTimeBetween('-1 year', '+1 year')),
            'end_datetime' => fn (array $attributes) => Carbon::parse($this->faker->dateTimeBetween($attributes['start_datetime']->add(new DateInterval('PT1H')), $attributes['start_datetime']->add(new DateInterval('PT3H')))),
        ];
    }
}
