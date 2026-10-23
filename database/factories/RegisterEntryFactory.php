<?php

namespace Database\Factories;

use App\Enums\RegisterStatus;
use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RegisterEntry>
 */
class RegisterEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * A register holds one row per member per date per ensemble, so the
     * defaults create their own member, date and ensemble rather than reusing
     * existing ones, which would keep colliding with that unique index.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Member]),
            'term_date_id' => TermDate::factory()->past(),
            'ensemble_id' => Ensemble::factory(),
            'status' => $this->faker->randomElement(RegisterStatus::cases()),
            'notes' => null,
            'marked_by_user_id' => User::factory()->state(['role' => UserRole::Moderator]),
        ];
    }

    /**
     * An entry for a member of the given ensemble at the given date, as the
     * register itself is always taken.
     */
    public function forMember(User $user, TermDate $term_date, Ensemble $ensemble): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'term_date_id' => $term_date->id,
            'ensemble_id' => $ensemble->id,
        ]);
    }
}
