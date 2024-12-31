<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\TermDate;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => $this->faker->randomElement(AttendanceStatus::cases()),
            'edit_ip' => $this->faker->ipv4,
            'user_id' => User::inRandomOrder()->first(),
            'edit_user_id' => User::inRandomOrder()->first(),
            'term_date_id' => TermDate::inRandomOrder()->first(),
        ];
    }
}