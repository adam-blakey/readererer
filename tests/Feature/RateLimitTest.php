<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

test('every throttled route counts under its own prefix', function () {
    $unprefixed = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => collect($route->gatherMiddleware())
            ->contains(fn ($middleware) => is_string($middleware)
                && str_starts_with($middleware, 'throttle:')
                && count(explode(',', $middleware)) < 3))
        ->map(fn ($route) => $route->uri())
        ->values();

    expect($unprefixed->all())->toBe([]);
});

test('routes that check a password or send emails are throttled', function (string $name) {
    expect(collect(Route::getRoutes()->getByName($name)->gatherMiddleware())
        ->contains(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'throttle:')))->toBeTrue();
})->with([
    'password.email',
    'password.store',
    'password.update',
    'login-link.email',
    'login-link.login',
    'notifications.resend',
    'term-dates.send-attendance-list',
    'term-dates.send-setup-reminder',
    'term-dates.send-van-driver-reminder',
    'users.password-reset-link',
]);

test('failed logins from one address are limited across usernames', function () {
    $user = User::factory()->create();

    foreach (range(1, 20) as $attempt) {
        $this->post('/login', ['username' => "nobody{$attempt}", 'password' => 'wrong-password']);
    }

    $this->post('/login', ['username' => $user->username, 'password' => 'password'])
        ->assertSessionHasErrors('username');
    $this->assertGuest();

    $this->travel(61)->seconds();

    $this->post('/login', ['username' => $user->username, 'password' => 'password']);
    $this->assertAuthenticated();
});

test('successful logins do not count towards the address limit', function () {
    $user = User::factory()->create();

    foreach (range(1, 25) as $attempt) {
        $this->post('/login', ['username' => $user->username, 'password' => 'password']);
        $this->assertAuthenticated();
        $this->post('/logout');
    }
});

test('confirming a password is limited to six attempts a minute', function () {
    $this->actingAs(User::factory()->create());

    foreach (range(1, 6) as $attempt) {
        $this->post('/confirm-password', ['password' => 'wrong-password'])->assertSessionHasErrors();
    }

    $this->post('/confirm-password', ['password' => 'password'])->assertTooManyRequests();
});

test('a throttled login link request gets the same answer as a sent one', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('login-link.email'), ['email' => $user->email]);
    $this->post(route('login-link.email'), ['email' => $user->email])
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();

    Notification::assertCount(1);
});
