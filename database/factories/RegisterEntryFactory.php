<?php

namespace Database\Factories;

use App\Enums\RegisterStatus;
use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\RegisterEntry;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegisterEntry>
 */
class RegisterEntryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Member]),
            'term_date_id' => TermDate::factory(),
            'ensemble_id' => Ensemble::factory(),
            'status' => $this->faker->randomElement(RegisterStatus::cases()),
            'marked_by_user_id' => User::factory()->state(['role' => UserRole::Moderator]),
        ];
    }
}
