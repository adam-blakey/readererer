<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\EnsembleAdmin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnsembleAdmin>
 */
class EnsembleAdminFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ensemble_id' => Ensemble::factory(),
            'admin_id' => User::factory()->state(['role' => UserRole::Admin]),
        ];
    }
}
