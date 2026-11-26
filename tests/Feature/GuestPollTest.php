<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Features\GuestPolls;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use Laravel\Pennant\Feature;

function enable_guest_polls(): void
{
    config(['app.readererer_feature_flags' => [GuestPolls::class => ['enabled' => true]]]);
    Feature::flushCache();
}

beforeEach(function () {
    $this->ensemble = Ensemble::factory()->create();
    $this->term = Term::factory()->create();
    $this->termDate = make_term_date($this->term);
    $this->guest = make_user(UserRole::Guest);
    join_ensemble($this->guest, $this->ensemble);
    $this->other = make_user(UserRole::Member);
    join_ensemble($this->other, $this->ensemble);
    $this->url = route('attendance.poll.show', ['ensemble' => $this->ensemble->slug, 'term' => $this->term->slug]);
    $this->storeUrl = route('attendance.poll.store', ['ensemble' => $this->ensemble->slug, 'term' => $this->term->slug]);
});

test('guests cannot open or answer polls while the flag is off', function () {
    $this->actingAs($this->guest)->get($this->url)->assertForbidden();
    $this->actingAs($this->guest)
        ->post($this->storeUrl, ["status-t{$this->termDate->id}m{$this->guest->id}" => AttendanceStatus::Attending->value])
        ->assertForbidden();

    expect(Attendance::count())->toBe(0);
});

test('with the flag on, a guest sees only their own replies', function () {
    enable_guest_polls();

    $response = $this->actingAs($this->guest)->get($this->url)
        ->assertOk()
        ->assertSee("status-t{$this->termDate->id}m{$this->guest->id}", false)
        ->assertDontSee("status-t{$this->termDate->id}m{$this->other->id}", false)
        ->assertDontSee($this->other->name)
        ->assertDontSee(__('Only show me'));

    expect($response->viewData('members')->pluck('id')->all())->toBe([$this->guest->id]);
});

test('with the flag on, a guest can change only their own replies', function () {
    enable_guest_polls();

    $this->actingAs($this->guest)->post($this->storeUrl, [
        "status-t{$this->termDate->id}m{$this->guest->id}" => AttendanceStatus::Attending->value,
        "status-t{$this->termDate->id}m{$this->other->id}" => AttendanceStatus::NotAttending->value,
    ])->assertRedirect();

    expect(Attendance::pluck('user_id')->all())->toBe([$this->guest->id]);
});

test('with the flag on, guests still cannot open polls of ensembles they do not play in', function (bool $joins) {
    enable_guest_polls();
    $guest = make_user(UserRole::Guest);
    if ($joins) {
        join_ensemble_without_instrument($guest, $this->ensemble);
    }

    $this->actingAs($guest)->get($this->url)->assertForbidden();
    $this->actingAs($guest)
        ->post($this->storeUrl, ["status-t{$this->termDate->id}m{$guest->id}" => AttendanceStatus::Attending->value])
        ->assertForbidden();
})->with([
    'not in the ensemble' => [false],
    'in the ensemble without an instrument' => [true],
]);

test('with the flag on, guests still cannot see poll history', function () {
    enable_guest_polls();

    $this->actingAs($this->guest)
        ->get(route('attendance.poll.history', ['ensemble' => $this->ensemble->slug, 'term' => $this->term->slug, 'user' => $this->guest]))
        ->assertForbidden();
});

test('members still see everyone with the flag on', function () {
    enable_guest_polls();

    $this->actingAs($this->other)->get($this->url)
        ->assertOk()
        ->assertSee("status-t{$this->termDate->id}m{$this->guest->id}", false);
});
