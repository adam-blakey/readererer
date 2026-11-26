<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\User;
use App\Notifications\EmailLink;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->ensemble = Ensemble::factory()->create();
    $this->login = make_user(UserRole::Ensemble);
    join_ensemble_without_instrument($this->login, $this->ensemble);
});

function claimable_member(Ensemble $ensemble, array $attributes = []): User
{
    $member = make_user(UserRole::Member, $attributes);
    join_ensemble_without_instrument($member, $ensemble);

    return $member;
}

test('the ensemble login is invited to set up an account on the home page and dashboard', function () {
    $this->actingAs($this->login)->get('/')->assertSee(route('claim-account.index'));
    $this->actingAs($this->login)->get('/dashboard')->assertSee(route('claim-account.index'));
    $this->actingAs(make_user())->get('/')->assertDontSee(route('claim-account.index'));
});

test('the page lists the ensemble members with claim buttons or usernames', function () {
    $new = claimable_member($this->ensemble, ['first_name' => 'Newbie']);
    $set_up = claimable_member($this->ensemble, ['first_name' => 'Settled', 'username' => 'settled.user', 'last_login_at' => now()]);
    $outsider = make_user(UserRole::Member, ['first_name' => 'Outsider']);

    $this->actingAs($this->login)->get('/claim-account')
        ->assertOk()
        ->assertSee('Newbie')
        ->assertSee(route('claim-account.store', $new))
        ->assertSee('Settled')
        ->assertSee('settled.user')
        ->assertDontSee(route('claim-account.store', $set_up))
        ->assertDontSee('Outsider');

    $this->actingAs(make_user())->get('/claim-account')->assertForbidden();
});

test('claiming emails a login link and makes the member choose a password', function () {
    Notification::fake();
    $member = claimable_member($this->ensemble);

    $this->actingAs($this->login)->post(route('claim-account.store', $member))->assertRedirect()->assertSessionHas('status');

    Notification::assertSentTo($member, EmailLink::class);
    expect($member->fresh()->must_change_password)->toBeTrue();
});

test('members who have logged in, have no email or are in another ensemble cannot be claimed', function () {
    Notification::fake();

    foreach ([
        claimable_member($this->ensemble, ['last_login_at' => now()]),
        claimable_member($this->ensemble, ['email' => null]),
        make_user(),
    ] as $member) {
        $this->actingAs($this->login)->post(route('claim-account.store', $member))->assertForbidden();
    }

    $this->actingAs(make_user(UserRole::Admin))->post(route('claim-account.store', claimable_member($this->ensemble)))->assertForbidden();
    Notification::assertNothingSent();
});

test('logging in records the time', function () {
    $member = make_user(UserRole::Member, ['password' => 'secret-password']);

    $this->post('/login', ['username' => $member->username, 'password' => 'secret-password']);

    expect($member->fresh()->last_login_at)->not->toBeNull();
});
