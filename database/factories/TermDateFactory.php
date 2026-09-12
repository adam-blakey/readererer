<?php

namespace Database\Factories;

use App\Models\Ensemble;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TermDate>
 */
class TermDateFactory extends Factory
{
    /**
     * Define the model's default state: an evening rehearsal, which applies to
     * every ensemble. Concerts belong to one ensemble — see concertFor().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::instance($this->faker->dateTimeBetween('-2 months', '+2 months'))->setTime(19, 30);

        return [
            'term_id' => Term::factory(),
            'start_datetime' => $start,
            'end_datetime' => $start->copy()->addHours(2),
            'concert_ensemble_id' => null,
        ];
    }

    /**
     * That ensemble's concert, rather than a rehearsal everyone attends.
     */
    public function concertFor(Ensemble $ensemble): static
    {
        return $this->state(fn (array $attributes) => [
            'concert_ensemble_id' => $ensemble->id,
        ]);
    }

    /**
     * A date that has already happened — one a register can be taken for.
     */
    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $start = now()->subDays($this->faker->numberBetween(1, 60))->setTime(19, 30);

            return [
                'start_datetime' => $start,
                'end_datetime' => $start->copy()->addHours(2),
            ];
        });
    }

    /**
     * A date still to come — one the attendance poll can be answered for.
     */
    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $start = now()->addDays($this->faker->numberBetween(1, 60))->setTime(19, 30);

            return [
                'start_datetime' => $start,
                'end_datetime' => $start->copy()->addHours(2),
            ];
        });
    }
}
