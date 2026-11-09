<?php

use App\Enums\AttendanceStatus;
use App\Enums\RegisterStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\RegisterEntry;
use App\Models\Term;
use App\Models\TermDate;
use Illuminate\Support\Facades\DB;

function populate_ensemble(Ensemble $ensemble, Term $term, int $members): void
{
    $families = collect(range(0, 3))->map(fn ($i) => make_instrument_family("Family $i"));
    $dates = TermDate::where('term_id', $term->id)->get();

    foreach (range(1, $members) as $i) {
        $user = make_user(UserRole::Member);
        join_ensemble($user, $ensemble, $families[$i % 4], chr(65 + $i % 5), $i);

        foreach ($dates as $date) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'term_date_id' => $date->id,
                'ensemble_id' => $ensemble->id,
                'status' => AttendanceStatus::Attending,
                'dep_name' => null,
            ]);
            RegisterEntry::factory()->create([
                'user_id' => $user->id,
                'term_date_id' => $date->id,
                'ensemble_id' => $ensemble->id,
                'status' => RegisterStatus::Present,
                'marked_by_user_id' => $user->id,
            ]);
        }
    }
}

function count_queries(string $url): int
{
    $admin = make_user(UserRole::Admin);
    DB::flushQueryLog();
    DB::enableQueryLog();
    test()->actingAs($admin)->get($url)->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

dataset('pages', [
    'dashboard' => fn ($ensemble, $term, $date) => '/dashboard',
    'poll' => fn ($ensemble, $term, $date) => route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]),
    'register' => fn ($ensemble, $term, $date) => route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $date]),
    'term show' => fn ($ensemble, $term, $date) => route('terms.show', $term),
    'users index' => fn ($ensemble, $term, $date) => route('users.index'),
    'ensembles index' => fn ($ensemble, $term, $date) => route('ensembles.index'),
    'seating plan' => fn ($ensemble, $term, $date) => route('ensembles.seating-plan.show', $ensemble),
]);

test('the page runs the same number of queries however many members there are', function (Closure $page) {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    foreach (range(1, 3) as $i) {
        TermDate::factory()->create([
            'term_id' => $term->id,
            'start_datetime' => now()->addDays($i)->setTime(19, 0),
            'end_datetime' => now()->addDays($i)->setTime(21, 0),
        ]);
    }
    $date = TermDate::where('term_id', $term->id)->first();

    populate_ensemble($ensemble, $term, 3);
    $few = count_queries($page($ensemble, $term, $date));

    populate_ensemble($ensemble, $term, 12);
    $many = count_queries($page($ensemble, $term, $date));

    expect($many)->toBe($few);
})->with('pages');
