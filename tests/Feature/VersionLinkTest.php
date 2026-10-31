<?php

use App\Enums\UserRole;

test('the footer links a release version to its GitHub release', function () {
    config(['app.version' => 'v1.4.0']);

    $this->actingAs(make_user(UserRole::Member))->get('/dashboard')
        ->assertSee('href="https://github.com/adam-blakey/readererer/releases/tag/v1.4.0"', false)
        ->assertSee('v1.4.0');
});

test('the footer links a dev version to its pull request', function () {
    config(['app.version' => 'v1.4.0-2-gabc1234+pr62']);

    $this->actingAs(make_user(UserRole::Member))->get('/dashboard')
        ->assertSee('href="https://github.com/adam-blakey/readererer/pull/62"', false);
});
