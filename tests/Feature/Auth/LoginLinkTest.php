<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\LoginLink;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

function requestLoginLink(User $user): string
{
    test()->post(route('login-link.email'), ['email' => $user->email]);

    $token = null;
    Notification::assertSentTo($user, LoginLink::class, function (LoginLink $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    return $token;
}

test('the login page links to the login link form', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('href="'.route('login-link.request').'"', false);

    $this->get(route('login-link.request'))
        ->assertOk()
        ->assertSee('Shared ensemble logins cannot log in by email.');
});

test('a member can log in with an emailed link, once', function () {
    Notification::fake();
    $user = User::factory()->create(['role' => UserRole::Member]);

    $token = requestLoginLink($user);
    $url = route('login-link.show', ['token' => $token, 'email' => $user->email]);

    // Opening the link alone does not log in, so link scanners cannot use it up.
    $this->get($url)->assertOk()->assertSee(route('login-link.login', $token), false);
    $this->assertGuest();

    $this->post(route('login-link.login', $token), ['email' => $user->email])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);

    auth()->logout();

    $this->post(route('login-link.login', $token), ['email' => $user->email])
        ->assertRedirect(route('login-link.request'));
    $this->assertGuest();
});

test('a login link only works for the email it was sent to', function () {
    Notification::fake();
    $user = User::factory()->create();
    $other = User::factory()->create();

    $token = requestLoginLink($user);

    $this->post(route('login-link.login', $token), ['email' => $other->email])
        ->assertRedirect(route('login-link.request'));
    $this->get(route('login-link.show', ['token' => 'wrong', 'email' => $user->email]))
        ->assertRedirect(route('login-link.request'));
    $this->assertGuest();
});

test('shared ensemble logins cannot get or use a login link', function () {
    Notification::fake();
    $shared = User::factory()->create(['role' => UserRole::Ensemble]);

    $this->post(route('login-link.email'), ['email' => $shared->email])
        ->assertSessionHas('status');
    Notification::assertNothingSent();

    // Even with a valid token, for instance from a reset link sent before the account became shared.
    $token = Password::createToken($shared);
    $this->post(route('login-link.login', $token), ['email' => $shared->email])
        ->assertRedirect(route('login-link.request'));
    $this->assertGuest();
});

test('an unknown email gets the same answer as a known one', function () {
    Notification::fake();

    $this->post(route('login-link.email'), ['email' => 'nobody@example.com'])
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();
    Notification::assertNothingSent();
});
