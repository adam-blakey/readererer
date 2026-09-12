<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * An attendance record only means anything against an ensemble, one of its
     * members and a date, so the factory reuses the ones already in the
     * database — that is what makes the seeder's records hang together — and
     * creates them only when there are none, so that a bare
     * Attendance::factory()->create() works on an empty database too.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ensemble = Ensemble::inRandomOrder()->first() ?? Ensemble::factory()->create();
        $user = $this->memberOf($ensemble);
        $term_date = TermDate::inRandomOrder()->first() ?? TermDate::factory()->create();

        return [
            'status' => $this->faker->randomElement(AttendanceStatus::cases()),
            'edit_ip' => $this->faker->ipv4(),
            'user_id' => $user->id,
            'edit_user_id' => $this->editor() ?? $user->id,
            'term_date_id' => $term_date->id,
            'ensemble_id' => $ensemble->id,
        ];
    }

    /**
     * A member of the given ensemble — the shared Ensemble login is nobody's
     * attendance — or a new member of it when the ensemble has none.
     */
    private function memberOf(Ensemble $ensemble): User
    {
        $member = User::where('role', '!=', UserRole::Ensemble)
            ->whereHas('ensembles', fn (Builder $query) => $query->whereKey($ensemble->id))
            ->inRandomOrder()
            ->first();

        if ($member !== null) {
            return $member;
        }

        $member = User::factory()->create(['role' => UserRole::Member]);
        $member->ensembles()->attach($ensemble->id, [
            'instrument_family_id' => (InstrumentFamily::inRandomOrder()->first() ?? InstrumentFamily::factory()->create())->id,
        ]);

        return $member;
    }

    /**
     * Whoever recorded the answer: any real user, since members answer for
     * themselves and moderators answer for them.
     */
    private function editor(): ?int
    {
        return User::where('role', '!=', UserRole::Ensemble)
            ->inRandomOrder()
            ->first()
            ?->id;
    }
}
