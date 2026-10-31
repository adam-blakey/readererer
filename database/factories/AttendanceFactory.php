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
        $ensemble = Ensemble::inRandomOrder()->first();
        $user = User::inRandomOrder()
            ->where('role', '!=', UserRole::Ensemble)
            ->whereHas('ensembles', function ($query) use ($ensemble) {
                $query->where('ensemble_id', $ensemble->id);
            })
            ->first();

        return [
            'status' => $this->faker->randomElement(AttendanceStatus::cases()),
            'edit_ip' => $this->faker->ipv4,
            'user_id' => $user->id,
            'edit_user_id' => User::inRandomOrder()->first(),
            'term_date_id' => TermDate::inRandomOrder()->first(),
            'ensemble_id' => $ensemble->id,
        ];
    }
}
