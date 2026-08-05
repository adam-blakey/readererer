<?php

namespace Database\Seeders;

use App\Enums\RegisterStatus;
use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\RegisterEntry;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Database\Seeder;

class RegisterEntrySeeder extends Seeder
{
    /**
     * Fill in registers for dates that have already happened. Registers are one
     * row per member per date per ensemble, so they are built by walking the
     * dates rather than through a factory, which would keep colliding with the
     * unique index.
     */
    public function run(): void
    {
        $ensembles = Ensemble::with('users')->get();
        $marker = User::where('role', UserRole::Admin)->inRandomOrder()->first();

        $past_dates = TermDate::where('start_datetime', '<', now())
            ->orderByDesc('start_datetime')
            ->limit(10)
            ->get();

        foreach ($past_dates as $term_date) {
            foreach ($ensembles as $ensemble) {
                if (! $term_date->appliesToEnsemble($ensemble)) {
                    continue;
                }

                $members = $ensemble->users->filter(fn ($user) => $user->pivot->instrument_family_id !== null);

                foreach ($members as $member) {
                    RegisterEntry::updateOrCreate(
                        [
                            'user_id' => $member->id,
                            'term_date_id' => $term_date->id,
                            'ensemble_id' => $ensemble->id,
                        ],
                        [
                            // Most people turn up.
                            'status' => fake()->randomElement([
                                RegisterStatus::Present,
                                RegisterStatus::Present,
                                RegisterStatus::Present,
                                RegisterStatus::Late,
                                RegisterStatus::Absent,
                            ]),
                            'marked_by_user_id' => $marker?->id,
                        ]
                    );
                }
            }
        }
    }
}
