<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;

test('the home page is public', function () {
    $this->get('/')->assertOk();
});

test('the dashboard is forbidden for guests', function () {
    $this->get('/dashboard')->assertForbidden();
});

test('the dashboard is available to every authenticated role', function (UserRole $role) {
    $user = make_user($role);

    if ($role === UserRole::Ensemble) {
        // Ensemble logins are redirected to their ensemble's page, so they must belong to one.
        join_ensemble($user, Ensemble::factory()->create());
    }

    $this->actingAs($user)->get('/dashboard')->assertOk();
})->with([UserRole::Ensemble, UserRole::Member, UserRole::Moderator, UserRole::Admin]);

test('resource indexes redirect guests to the login page', function (string $uri) {
    $this->get($uri)->assertRedirect('/login');
})->with(['/composers', '/ensembles', '/pieces', '/setlists', '/terms', '/users', '/setup-groups']);

test('composer, piece and setlist indexes are forbidden for every role', function (string $uri, UserRole $role) {
    // The Composer, Piece and Setlist policies currently deny viewAny to everyone.
    $this->actingAs(make_user($role))->get($uri)->assertForbidden();
})->with(['/composers', '/pieces', '/setlists'])
    ->with([UserRole::Member, UserRole::Moderator, UserRole::Admin]);

test('the ensembles index is admin only', function () {
    $this->actingAs(make_user(UserRole::Moderator))->get('/ensembles')->assertForbidden();
    $this->actingAs(make_user(UserRole::Admin))->get('/ensembles')->assertOk();
});

test('term, user and setup group indexes only require authentication', function (string $uri) {
    // These controllers do not call authorizeResource, so any logged-in user may view them.
    $this->actingAs(make_user(UserRole::Member))->get($uri)->assertOk();
})->with(['/terms', '/users', '/setup-groups']);

test('ensemble show is limited to moderators and members of that ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $showUrl = route('ensembles.show', $ensemble);

    // Guests are redirected to log in.
    $this->get($showUrl)->assertRedirect('/login');

    // An ensemble login attached to this ensemble is allowed.
    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($ensembleLogin, $ensemble);
    $this->actingAs($ensembleLogin)->get($showUrl)->assertOk();

    // A member of the ensemble may view it; a member of a different ensemble may not.
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $this->actingAs($member)->get($showUrl)->assertOk();

    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, Ensemble::factory()->create());
    $this->actingAs($outsider)->get($showUrl)->assertForbidden();

    $this->actingAs(make_user(UserRole::Moderator))->get($showUrl)->assertOk();
    $this->actingAs(make_user(UserRole::Admin))->get($showUrl)->assertOk();
});

test('the attendance index requires moderator or above', function () {
    $url = route('attendance.index');

    $this->get($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Ensemble))->get($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Member))->get($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get($url)->assertOk();
    $this->actingAs(make_user(UserRole::Admin))->get($url)->assertOk();
});

test('the attendance poll page requires permission to view the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    // The poll view requires the term to have at least one date.
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $url = route('attendance.poll', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

    $this->get($url)->assertForbidden();

    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($ensembleLogin, $ensemble);
    $this->actingAs($ensembleLogin)->get($url)->assertOk();

    // An ensemble login for a different ensemble may not view this one's poll.
    $otherLogin = make_user(UserRole::Ensemble);
    join_ensemble($otherLogin, Ensemble::factory()->create());
    $this->actingAs($otherLogin)->get($url)->assertForbidden();

    // A member of a different ensemble may not view the poll either.
    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, Ensemble::factory()->create());
    $this->actingAs($outsider)->get($url)->assertForbidden();

    $this->actingAs(make_user(UserRole::Moderator))->get($url)->assertOk();
});

test('submitting the attendance poll requires the admin-only create permission', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $url = route('attendance.poll-store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

    $this->post($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Ensemble))->post($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Member))->post($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->post($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Admin))->post($url)->assertRedirect();
});

test('the settings page requires authentication', function () {
    $this->get('/settings')->assertRedirect('/login');
    $this->actingAs(make_user())->get('/settings')->assertOk();
});
