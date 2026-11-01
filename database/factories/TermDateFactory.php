<?php

namespace Database\Factories;

use App\Models\Term;
use App\Models\TermDate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TermDate>
 */
class TermDateFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $start = Carbon::instance($this->faker->dateTimeBetween('-2 months', '+2 months'))->setTime(19, 30);

        return [
            'term_id' => Term::factory(),
            'start_datetime' => $start,
            'end_datetime' => $start->copy()->addHours(2),
        ];
    }
}
