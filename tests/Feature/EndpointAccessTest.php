<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\SetupGroup;
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

test('term, user and setup group indexes require moderator or above', function (string $uri) {
    $this->actingAs(make_user(UserRole::Member))->get($uri)->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get($uri)->assertOk();
})->with(['/terms', '/users', '/setup-groups']);

test('members cannot change terms, setup groups or ensemble membership', function () {
    $member = make_user(UserRole::Member);
    $term = Term::factory()->create();
    $setupGroup = SetupGroup::factory()->create();
    $ensemble = Ensemble::factory()->create();
    $player = make_user(UserRole::Member);
    join_ensemble($player, $ensemble);

    $this->actingAs($member)->post(route('terms.store'), ['name' => 'New', 'slug' => 'new'])->assertForbidden();
    $this->actingAs($member)->delete(route('terms.destroy', $term))->assertForbidden();
    $this->actingAs($member)->delete(route('setupgroups.destroy', $setupGroup))->assertForbidden();
    $this->actingAs($member)->delete(route('ensembles.members.destroy', [$ensemble, $player]))->assertForbidden();
    $this->actingAs($member)->post(route('ensembles.members.store', $ensemble), [
        'user_id' => $member->id,
        'instrument_family_id' => make_instrument_family()->id,
    ])->assertForbidden();

    expect($term->fresh()->trashed())->toBeFalse();
    expect($setupGroup->fresh()->trashed())->toBeFalse();
    expect($ensemble->users()->pluck('users.id')->all())->toBe([$player->id]);
});

test('restoring a deleted record requires the restore permission', function () {
    $term = Term::factory()->create();
    $term->delete();

    $this->actingAs(make_user(UserRole::Moderator))->patch(route('terms.restore', $term->id))->assertForbidden();
    expect($term->fresh()->trashed())->toBeTrue();

    $this->actingAs(make_user(UserRole::Admin))->patch(route('terms.restore', $term->id))->assertRedirect();
    expect($term->fresh()->trashed())->toBeFalse();
});

test('ensemble show is limited to moderators and members of that ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $showUrl = route('ensembles.show', $ensemble);

    $this->get($showUrl)->assertRedirect('/login');

    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($ensembleLogin, $ensemble);
    $this->actingAs($ensembleLogin)->get($showUrl)->assertOk();

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
    $url = route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

    $this->get($url)->assertForbidden();

    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($ensembleLogin, $ensemble);
    $this->actingAs($ensembleLogin)->get($url)->assertOk();

    $otherLogin = make_user(UserRole::Ensemble);
    join_ensemble($otherLogin, Ensemble::factory()->create());
    $this->actingAs($otherLogin)->get($url)->assertForbidden();

    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, Ensemble::factory()->create());
    $this->actingAs($outsider)->get($url)->assertForbidden();

    $this->actingAs(make_user(UserRole::Moderator))->get($url)->assertOk();
});

test('submitting the attendance poll requires permission to view the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $url = route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

    $this->post($url)->assertForbidden();

    foreach ([UserRole::Ensemble, UserRole::Member] as $role) {
        $insider = make_user($role);
        join_ensemble($insider, $ensemble);
        $this->actingAs($insider)->post($url)->assertRedirect();

        $outsider = make_user($role);
        join_ensemble($outsider, Ensemble::factory()->create());
        $this->actingAs($outsider)->post($url)->assertForbidden();
    }

    $this->actingAs(make_user(UserRole::Moderator))->post($url)->assertRedirect();
    $this->actingAs(make_user(UserRole::Admin))->post($url)->assertRedirect();
});

test('the settings page requires authentication', function () {
    $this->get('/settings')->assertRedirect('/login');
    $this->actingAs(make_user())->get('/settings')->assertOk();
});
