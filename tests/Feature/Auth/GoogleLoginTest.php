<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function enable_google_login(array $config = []): void
{
    config(array_merge([
        'app.readererer_google_login' => true,
        'services.google.client_id' => 'client-id',
        'services.google.client_secret' => 'client-secret',
    ], $config));
}

function google_returns(string $email, bool $verified = true): void
{
    $user = (new SocialiteUser)
        ->setRaw(['email' => $email, 'email_verified' => $verified, 'given_name' => 'Ada', 'family_name' => 'Lovelace'])
        ->map(['email' => $email, 'name' => 'Ada Lovelace']);

    Socialite::shouldReceive('driver->user')->andReturn($user);
}

test('the Google button and routes are off unless Google login is set up', function () {
    config(['app.readererer_google_login' => true, 'services.google.client_id' => 'client-id', 'services.google.client_secret' => null]);

    $this->get('/login')->assertOk()->assertDontSee('Login with Google');
    $this->get(route('google.redirect'))->assertNotFound();
    $this->get(route('google.callback'))->assertNotFound();
});

test('the Google button sends the user to Google', function () {
    enable_google_login();

    $this->get('/login')->assertSee(route('google.redirect'), false);

    $redirect = $this->get(route('google.redirect'))->assertRedirect()->headers->get('Location');

    expect($redirect)->toStartWith('https://accounts.google.com/')
        ->and($redirect)->toContain('client_id=client-id')
        ->and($redirect)->toContain(urlencode(route('google.callback')));
});

test('a user logs in with the Google account that has their email address', function () {
    enable_google_login();
    $user = make_user(UserRole::Member, ['email' => 'ada@example.org']);
    google_returns('Ada@Example.org');

    $this->get(route('google.callback'))->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('an unverified Google email address is refused', function () {
    enable_google_login();
    make_user(UserRole::Member, ['email' => 'ada@example.org']);
    google_returns('ada@example.org', verified: false);

    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('an email address shared by two accounts in different cases is refused', function () {
    enable_google_login();
    make_user(UserRole::Member, ['email' => 'ada@example.org']);
    make_user(UserRole::Member, ['email' => 'Ada@example.org']);
    google_returns('ada@example.org');

    $this->get(route('google.callback'))->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('an unknown email address is refused when auto-registration is off', function () {
    enable_google_login(['app.readererer_google_register_domain' => 'example.org']);
    google_returns('ada@example.org');

    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('username');

    $this->assertGuest();
    expect(User::where('email', 'ada@example.org')->exists())->toBeFalse();
});

test('a new email address on the registration domain gets an account with the chosen role', function () {
    enable_google_login(['app.readererer_google_register_domain' => 'example.org', 'app.readererer_google_register_role' => UserRole::Moderator->value]);
    google_returns('ada@example.org');

    $this->get(route('google.callback'))->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'ada@example.org')->sole();
    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe(UserRole::Moderator)
        ->and($user->first_name)->toBe('Ada')
        ->and($user->last_name)->toBe('Lovelace')
        ->and($user->username)->toBe('ada.lovelace');
});

test('a new email address on another domain gets no account', function (string $email) {
    enable_google_login(['app.readererer_google_register_domain' => 'example.org', 'app.readererer_google_register_role' => UserRole::Member->value]);
    google_returns($email);

    $this->get(route('google.callback'))->assertSessionHasErrors('username');

    $this->assertGuest();
    expect(User::where('email', $email)->exists())->toBeFalse();
})->with(['ada@other.org', 'ada@notexample.org', 'ada@example.org.evil.com']);

test('a shared ensemble login cannot log in with Google', function () {
    enable_google_login(['app.readererer_google_register_domain' => 'example.org', 'app.readererer_google_register_role' => UserRole::Member->value]);
    make_user(UserRole::Ensemble, ['email' => 'band@example.org']);
    google_returns('band@example.org');

    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('username');

    $this->assertGuest();
    expect(User::where('email', 'band@example.org')->count())->toBe(1);
});
