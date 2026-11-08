<?php

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\RegisterEntry;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;

test('the home page is public', function () {
    $this->get('/')->assertOk();
});

test('the dashboard sends guests to log in', function () {
    $this->get('/dashboard')->assertRedirectContains('/login?redirect=');
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
    $this->get($uri)->assertRedirectContains('/login?redirect=');
})->with(['/composers', '/ensembles', '/pieces', '/setlists', '/terms', '/users', '/setup-groups']);

test('composer, piece and setlist indexes are forbidden for every role', function (string $uri, UserRole $role) {
    // The Composer, Piece and Setlist policies currently deny viewAny to everyone.
    $this->actingAs(make_user($role))->get($uri)->assertForbidden();
})->with(['/composers', '/pieces', '/setlists'])
    ->with([UserRole::Member, UserRole::Moderator, UserRole::Admin]);

test('ensemble, instrument family, term, user and setup group indexes require moderator or above', function (string $uri) {
    $this->actingAs(make_user(UserRole::Member))->get($uri)->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get($uri)->assertOk();
})->with(['/ensembles', '/instrument-families', '/terms', '/users', '/setup-groups']);

test('moderators can view but not create or edit the records listed on the settings page', function (string $prefix, string $class) {
    $moderator = make_user(UserRole::Moderator);
    $record = $class::factory()->create();

    $this->actingAs($moderator)->get(route("{$prefix}.show", $record))->assertOk();
    $this->actingAs($moderator)->get(route("{$prefix}.create"))->assertForbidden();
    $this->actingAs($moderator)->get(route("{$prefix}.edit", $record))->assertForbidden();
    $this->actingAs($moderator)->post(route("{$prefix}.store"), ['name' => 'New'])->assertForbidden();
    $this->actingAs($moderator)->put(route("{$prefix}.update", $record), ['name' => 'Changed'])->assertForbidden();
    $this->actingAs($moderator)->delete(route("{$prefix}.destroy", $record))->assertForbidden();

    expect($record->fresh()->name)->not->toBe('Changed')
        ->and($record->fresh()->trashed())->toBeFalse();
})->with([
    ['ensembles', Ensemble::class],
    ['instrumentfamilys', InstrumentFamily::class],
    ['terms', Term::class],
    ['setupgroups', SetupGroup::class],
]);

test('moderators can create, update and delete users but not restore or erase them', function () {
    $moderator = make_user(UserRole::Moderator);
    $user = make_user(UserRole::Member);

    expect($moderator->can('create', User::class))->toBeTrue()
        ->and($moderator->can('update', $user))->toBeTrue()
        ->and($moderator->can('delete', $user))->toBeTrue()
        ->and($moderator->can('restore', $user))->toBeFalse()
        ->and($moderator->can('erase', $user))->toBeFalse()
        ->and($moderator->can('resetPassword', $user))->toBeFalse();
});

test('moderators can create and update attendance and registers but not delete them or send notifications', function () {
    $moderator = make_user(UserRole::Moderator);
    $termDate = make_term_date(Term::factory()->create());

    expect($moderator->can('create', Attendance::class))->toBeTrue()
        ->and($moderator->can('create', RegisterEntry::class))->toBeTrue()
        ->and($moderator->can('delete', new RegisterEntry))->toBeFalse()
        ->and($moderator->can('sendNotifications', $termDate))->toBeFalse()
        ->and(make_user(UserRole::Admin)->can('sendNotifications', $termDate))->toBeTrue();
});

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

    $this->get($showUrl)->assertRedirectContains('/login?redirect=');

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

    $this->get($url)->assertRedirectContains('/login?redirect=');
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

    $this->get($url)->assertRedirectContains('/login?redirect=');

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

test('moderators can view the settings page but only admins can change it', function () {
    $this->get('/settings')->assertRedirectContains('/login?redirect=');
    $this->actingAs(make_user(UserRole::Member))->get('/settings')->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get('/settings')->assertOk();
    $this->actingAs(make_user(UserRole::Moderator))->put('/settings')->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get('/settings/import')->assertForbidden();
    $this->actingAs(make_user(UserRole::Admin))->get('/settings')->assertOk();
});
