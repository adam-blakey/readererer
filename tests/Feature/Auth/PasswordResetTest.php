<?php

use App\Enums\EmailLinkType;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\EmailLink;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, EmailLink::class, fn (EmailLink $notification) => $notification->type === EmailLinkType::ResetPassword);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, EmailLink::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, EmailLink::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('resetting a password clears a pending forced change', function () {
    Notification::fake();

    $user = User::factory()->create();
    $user->forceFill(['must_change_password' => true])->save();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, EmailLink::class, function ($notification) use ($user) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ]);

        return true;
    });

    expect($user->refresh()->must_change_password)->toBeFalse();
});

test('shared ensemble logins cannot get or use a password reset link', function () {
    Notification::fake();
    $shared = User::factory()->create(['role' => UserRole::Ensemble]);

    $this->post('/forgot-password', ['email' => $shared->email])->assertSessionHasErrors('email');
    Notification::assertNothingSent();

    // Even with a valid token, for instance from a link sent before the account became shared.
    $this->post('/reset-password', [
        'token' => Password::createToken($shared),
        'email' => $shared->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('new-password', $shared->fresh()->password))->toBeFalse();
});
