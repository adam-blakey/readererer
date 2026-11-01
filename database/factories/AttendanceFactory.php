<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $ensemble = Ensemble::inRandomOrder()->first() ?? Ensemble::factory()->create();
        $user = $ensemble->users()->where('role', '!=', UserRole::Ensemble)->inRandomOrder()->first();

        if ($user === null) {
            $user = User::factory()->create(['role' => UserRole::Member]);
            $user->ensembles()->attach($ensemble);
        }

        return [
            'status' => $this->faker->randomElement(AttendanceStatus::cases()),
            'edit_ip' => $this->faker->ipv4(),
            'user_id' => $user->id,
            'edit_user_id' => $user->id,
            'term_date_id' => (TermDate::inRandomOrder()->first() ?? TermDate::factory()->create())->id,
            'ensemble_id' => $ensemble->id,
        ];
    }
}
