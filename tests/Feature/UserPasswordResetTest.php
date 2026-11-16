<?php

use App\Enums\EmailLinkType;
use App\Enums\UserRole;
use App\Features\AdminPasswordChange;
use App\Features\ConfirmPasswordForSensitiveChanges;
use App\Notifications\EmailLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;

test('an admin can email a user a password reset link', function () {
    Notification::fake();
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->from(route('users.show', $user))
        ->post(route('users.password-reset-link', $user))
        ->assertRedirect(route('users.show', $user))
        ->assertSessionHas('status');

    Notification::assertSentTo($user, EmailLink::class, fn (EmailLink $notification) => $notification->type === EmailLinkType::ResetPassword);
});

test('a reset link cannot be sent to a user without an email address', function () {
    Notification::fake();
    $user = make_user(UserRole::Member, ['email' => null]);
    make_user(UserRole::Member, ['email' => null]);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.password-reset-link', $user))
        ->assertStatus(422);

    Notification::assertNothingSent();
});

test('an admin can set a user\'s password', function (bool $mustChange) {
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->from(route('users.show', $user))
        ->put(route('users.password', $user), array_filter([
            'password' => 'a-new-Password-123',
            'password_confirmation' => 'a-new-Password-123',
            'must_change_password' => $mustChange ? '1' : null,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.show', $user));

    $user->refresh();
    expect(Hash::check('a-new-Password-123', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBe($mustChange);
})->with([true, false]);

test('a set password must be confirmed', function () {
    $user = make_user(UserRole::Member);
    $password = $user->password;

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('users.password', $user), ['password' => 'a-new-Password-123', 'password_confirmation' => 'different'])
        ->assertSessionHasErrors('password');

    expect($user->refresh()->password)->toBe($password);
});

test('only admins can reset another user\'s password', function (UserRole $role) {
    Notification::fake();
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user($role))->post(route('users.password-reset-link', $user))->assertForbidden();
    $this->actingAs(make_user($role))->put(route('users.password', $user), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123'])->assertForbidden();
})->with([UserRole::Moderator, UserRole::Member]);

test('admins cannot reset their own password from the user page', function () {
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->put(route('users.password', $admin), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123'])->assertForbidden();
});

test('the reset password button only shows to admins', function () {
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))->get(route('users.show', $user))->assertSee('modal-reset-password');
    $this->actingAs(make_user(UserRole::Moderator))->get(route('users.show', $user))->assertDontSee('modal-reset-password');
});

test('an admin cannot email a shared ensemble login a reset link', function () {
    Notification::fake();
    $shared = make_user(UserRole::Ensemble);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('users.show', $shared))
        ->assertSee('Shared ensemble logins cannot reset their password by email.')
        ->assertDontSee(route('users.password-reset-link', $shared));

    $this->actingAs($admin)->post(route('users.password-reset-link', $shared))->assertSessionHasErrors('reset_link');
    Notification::assertNothingSent();
});

function enable_admin_password_change(bool $confirmPassword = false): void
{
    config(['app.readererer_feature_flags' => [
        AdminPasswordChange::class => ['enabled' => true],
        ConfirmPasswordForSensitiveChanges::class => ['enabled' => $confirmPassword],
    ]]);
    Feature::flushCache();
}

test('who may set a user\'s password or send them a reset link', function (UserRole $actor, UserRole $target, bool $allowed) {
    enable_admin_password_change();
    Notification::fake();
    $user = make_user($target);
    $actingUser = make_user($actor);

    $this->actingAs($actingUser)->get(route('users.password.edit', $user))->assertStatus($allowed ? 200 : 403);
    $this->actingAs($actingUser)->post(route('users.password-reset-link', $user))->assertStatus($allowed ? 302 : 403);
    $this->actingAs($actingUser)->put(route('users.password', $user), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123'])->assertStatus($allowed ? 302 : 403);

    expect(Hash::check('a-new-Password-123', $user->fresh()->password))->toBe($allowed);
    $allowed ? Notification::assertSentTo($user, EmailLink::class) : Notification::assertNothingSent();
})->with([
    'member on member' => [UserRole::Member, UserRole::Member, false],
    'moderator on member' => [UserRole::Moderator, UserRole::Member, false],
    'moderator on moderator' => [UserRole::Moderator, UserRole::Moderator, false],
    'moderator on admin' => [UserRole::Moderator, UserRole::Admin, false],
    'admin on member' => [UserRole::Admin, UserRole::Member, true],
    'admin on moderator' => [UserRole::Admin, UserRole::Moderator, true],
    'admin on admin' => [UserRole::Admin, UserRole::Admin, true],
]);

test('nobody may set their own password from the user page', function (UserRole $role) {
    enable_admin_password_change();
    $user = make_user($role);

    $this->actingAs($user)->get(route('users.password.edit', $user))->assertForbidden();
})->with([UserRole::Member, UserRole::Moderator, UserRole::Admin]);

test('the set password page is not found while the flag is off', function () {
    $this->actingAs(make_user(UserRole::Admin))->get(route('users.password.edit', make_user()))->assertNotFound();
});

test('the user page shows the password buttons only with the flag on', function () {
    $user = make_user();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('users.show', $user))
        ->assertSee('modal-reset-password')
        ->assertDontSee('href="'.route('users.password.edit', $user).'"', false);

    enable_admin_password_change();

    $this->actingAs($admin)->get(route('users.show', $user))
        ->assertDontSee('modal-reset-password')
        ->assertSee(route('users.password-reset-link', $user))
        ->assertSee('href="'.route('users.password.edit', $user).'"', false);
    $this->actingAs(make_user(UserRole::Moderator))->get(route('users.show', $user))
        ->assertDontSee(route('users.password-reset-link', $user))
        ->assertDontSee('href="'.route('users.password.edit', $user).'"', false);
});

test('a shared ensemble login gets no reset email button but can have its password set', function () {
    enable_admin_password_change();
    $shared = make_user(UserRole::Ensemble);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('users.show', $shared))
        ->assertDontSee(route('users.password-reset-link', $shared))
        ->assertSee('href="'.route('users.password.edit', $shared).'"', false);
    $this->actingAs($admin)->get(route('users.password.edit', $shared))
        ->assertOk()
        ->assertDontSee('checked');
    $this->actingAs($admin)
        ->put(route('users.password', $shared), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123'])
        ->assertRedirect(route('users.show', $shared));

    expect(Hash::check('a-new-Password-123', $shared->fresh()->password))->toBeTrue();
});

test('a user given a password is held on the change password page', function () {
    enable_admin_password_change();
    $user = make_user();

    $this->actingAs(make_user(UserRole::Admin))->get(route('users.password.edit', $user))->assertSee('checked');
    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('users.password', $user), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123', 'must_change_password' => '1'])
        ->assertRedirect(route('users.show', $user));

    expect($user->fresh()->must_change_password)->toBeTrue();
    $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('password.edit'));
});

test('setting a password logs the user out everywhere', function () {
    config(['session.driver' => 'database']);
    $user = make_user();
    $other = make_user();
    foreach ([$user, $other] as $owner) {
        DB::table('sessions')->insert(['id' => Str::random(40), 'user_id' => $owner->id, 'payload' => '', 'last_activity' => time()]);
    }
    $token = $user->remember_token;

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('users.password', $user), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123']);

    expect(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $other->id)->exists())->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe($token);
});

test('setting a user\'s password asks for the password when that flag is on', function () {
    enable_admin_password_change(confirmPassword: true);
    $user = make_user();
    $password = $user->password;
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('users.password.edit', $user))->assertRedirect(route('password.confirm'));
    $this->actingAs($admin)
        ->put(route('users.password', $user), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123'])
        ->assertRedirect(route('password.confirm'));
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('users.password.edit', $user))
        ->assertOk();

    expect($user->fresh()->password)->toBe($password);
});

test('setting a password and sending a link are logged by user ID only', function () {
    Notification::fake();
    Log::spy();
    $user = make_user();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->post(route('users.password-reset-link', $user));
    $this->actingAs($admin)->put(route('users.password', $user), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123']);

    Log::shouldHaveReceived('notice')->with('User password reset link requested', ['user_id' => $user->id, 'status' => Password::RESET_LINK_SENT]);
    Log::shouldHaveReceived('notice')->with('User password set', ['user_id' => $user->id, 'must_change_password' => false]);
});
