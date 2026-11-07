<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;

test('a guest sent to log in returns to the page they asked for', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $user = make_user(UserRole::Admin);
    $poll = route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term], absolute: false);
    $login = route('login', ['redirect' => 'attendance.poll.show', 'parameters' => ['ensemble' => $ensemble->slug, 'term' => $term->slug]]);

    $this->get($poll)->assertRedirect($login);
    $this->get($login)->assertSee(e($login), false);

    $this->post($login, ['username' => $user->username, 'password' => 'password'])->assertRedirect($poll);
});

test('pages behind the auth middleware also pass their route to the login page', function () {
    $this->get('/users?sortby=name')
        ->assertRedirect(route('login', ['redirect' => 'users.index', 'parameters' => ['sortby' => 'name']]));
});

test('logging in ignores redirects that are not GET routes of this app', function (array $query) {
    $user = make_user(UserRole::Admin);

    $this->post(route('login', $query), ['username' => $user->username, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
})->with([
    'external url' => [['redirect' => 'https://example.com']],
    'unknown route' => [['redirect' => 'nope']],
    'post route' => [['redirect' => 'logout']],
    'missing parameters' => [['redirect' => 'attendance.poll.show']],
]);

test('signed-in users without access still get a 403', function () {
    $this->actingAs(make_user(UserRole::Member))->get('/users')->assertForbidden();
});
