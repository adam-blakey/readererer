<?php

use App\Enums\UserRole;

beforeEach(fn () => $this->withSession(['auth.password_confirmed_at' => 0]));

test('sensitive pages open without the password', function (string $route) {

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route($route))
        ->assertOk();
})->with(['settings.edit', 'settings.import', 'feature-flags.edit']);

test('saving sensitive changes asks for the password', function () {

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('feature-flags.update'))
        ->assertRedirect(route('password.confirm'));
});

test('saving sensitive changes works once the password has been confirmed', function () {

    $this->actingAs(make_user(UserRole::Admin))
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('feature-flags.update'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

test('confirming the password for a form with a password returns to the form and keeps the rest of what was typed', function () {
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->from(route('feature-flags.edit'))
        ->put(route('feature-flags.update'), ['typed' => 'kept', 'password' => 'secret'])
        ->assertRedirect(route('password.confirm'));

    submit_confirm_password_form($this, 'password')
        ->assertRedirect(route('feature-flags.edit'))
        ->assertSessionHasInput('typed', 'kept')
        ->assertSessionMissing('_old_input.password');
});

test('editing a user opens without the password but saving asks a moderator for it', function () {
    $moderator = make_user(UserRole::Moderator);
    $user = make_user();

    $this->actingAs($moderator)
        ->get(route('users.edit', $user))
        ->assertOk();

    $this->actingAs($moderator)
        ->put(route('users.update', $user), ['name' => 'New name'])
        ->assertRedirect(route('password.confirm'));
});

test('members saving their own profile are not asked for the password', function () {
    $member = make_user();

    $this->actingAs($member)
        ->put(route('users.update', $member))
        ->assertSessionHasErrors('email');
});

test('erasing a user asks for the password', function () {
    $user = make_user();

    $this->actingAs(make_user(UserRole::Admin))
        ->delete(route('users.erase', $user), ['confirm_name' => $user->name])
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->username)->not->toBe("erased-{$user->id}");
});

function submit_confirm_password_form($test, string $password)
{
    $document = new DOMDocument;
    @$document->loadHTML($test->get(route('password.confirm'))->getContent());
    $form = (new DOMXPath($document))->query('//form[.//input[@type="password"]]')->item(0);

    $fields = [];
    foreach ($form->getElementsByTagName('input') as $input) {
        $fields[$input->getAttribute('name')] = $input->getAttribute('type') === 'password' ? $password : $input->getAttribute('value');
    }

    return $test->post($form->getAttribute('action'), $fields);
}

test('confirming the password saves the change that asked for it', function () {
    $moderator = make_user(UserRole::Moderator);
    $user = make_user();
    $input = ['first_name' => $user->first_name, 'last_name' => $user->last_name, 'email' => 'changed@example.com', 'role' => $user->role->value];

    $this->actingAs($moderator)
        ->from(route('users.edit', $user))
        ->put(route('users.update', $user), $input)
        ->assertRedirect(route('password.confirm'));

    submit_confirm_password_form($this, 'password')
        ->assertRedirect(route('users.show', $user))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe('changed@example.com');
});

test('a change that fails validation after confirming the password returns to its form', function () {
    $moderator = make_user(UserRole::Moderator);
    $user = make_user();

    $this->actingAs($moderator)
        ->from(route('users.edit', $user))
        ->put(route('users.update', $user), ['first_name' => 'Kept', 'email' => 'not-an-email'])
        ->assertRedirect(route('password.confirm'));

    submit_confirm_password_form($this, 'password')
        ->assertRedirect(route('users.edit', $user))
        ->assertSessionHasErrors('email')
        ->assertSessionHasInput('first_name', 'Kept');
});

test('a wrong password does not save the change', function () {
    $moderator = make_user(UserRole::Moderator);
    $user = make_user();
    $email = $user->email;

    $this->actingAs($moderator)
        ->put(route('users.update', $user), ['first_name' => $user->first_name, 'last_name' => $user->last_name, 'email' => 'changed@example.com', 'role' => $user->role->value])
        ->assertRedirect(route('password.confirm'));

    submit_confirm_password_form($this, 'wrong-password')
        ->assertRedirect(route('password.confirm'))
        ->assertSessionHasErrors('password');

    expect($user->fresh()->email)->toBe($email);
});
