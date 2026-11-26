<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\User;
use App\Services\PollererImporter;
use Illuminate\Support\Facades\Hash;

function new_user_payload(UserRole $role): array
{
    return ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', 'role' => $role->value];
}

test('users cannot be created with the ensemble role', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('users.create'))
        ->assertOk()
        ->assertViewHas('fields', fn (array $fields) => ! array_key_exists(UserRole::Ensemble->value, $fields['role']['options']));

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.store'), new_user_payload(UserRole::Ensemble))
        ->assertSessionHasErrors('role');

    expect(User::where('email', 'ada@example.com')->exists())->toBeFalse();
});

test('user:create refuses the ensemble role', function () {
    $this->artisan('user:create', ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', '--role' => 'ensemble'])
        ->assertFailed();

    expect(User::where('email', 'ada@example.com')->exists())->toBeFalse();
});

test('a user cannot be changed to the ensemble role', function () {
    $member = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('users.update', $member), new_user_payload(UserRole::Ensemble))
        ->assertSessionHasErrors('role');

    expect($member->fresh()->role)->toBe(UserRole::Member);
});

test('a shared ensemble login keeps its role and has no email when edited', function () {
    $login = make_user(UserRole::Ensemble, ['email' => null]);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('users.edit', $login))
        ->assertOk()
        ->assertDontSee('name="role"', false)
        ->assertDontSee('name="email"', false)
        ->assertSee(__('A shared ensemble login keeps its role.'));

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('users.update', $login), new_user_payload(UserRole::Admin))
        ->assertSessionHasNoErrors();

    expect($login->fresh()->role)->toBe(UserRole::Ensemble)
        ->and($login->fresh()->first_name)->toBe('Ada')
        ->and($login->fresh()->email)->toBeNull();
});

test('creating an ensemble creates its shared login', function () {
    $this->actingAs(make_user(UserRole::Admin))->post(route('ensembles.store'), ['name' => 'Wind Band']);

    $ensemble = Ensemble::where('name', 'Wind Band')->firstOrFail();
    $login = $ensemble->sharedLogins()->sole();

    expect($login->role)->toBe(UserRole::Ensemble)
        ->and($login->username)->toBe('wind.band.ensemble')
        ->and($login->must_change_password)->toBeFalse()
        ->and($ensemble->users)->toBeEmpty();
});

test('a Pollererer import does not add shared logins beside the imported ones', function () {
    $export = json_decode(file_get_contents(base_path('tests/Fixtures/pollererer-export.json')), true);
    (new PollererImporter($export, make_user(UserRole::Admin)))->import();

    expect(User::where('role', UserRole::Ensemble)->count())->toBe(1)
        ->and(Ensemble::where('slug', 'concert-band')->firstOrFail()->sharedLogins->pluck('username')->all())->toBe(['concertband'])
        ->and(Ensemble::where('slug', 'jazz-group')->firstOrFail()->sharedLogins)->toBeEmpty();
});

test('an admin can set the shared login from the ensemble page', function () {
    $ensemble = Ensemble::factory()->create();
    $login = $ensemble->createSharedLogin();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertSee('value="'.$login->username.'"', false)
        ->assertSee(__('Save shared login'));

    $this->actingAs($admin)
        ->put(route('ensembles.login.update', $ensemble), [
            'username' => 'band',
            'email' => 'band@example.com',
            'password' => 'a-new-Password-123',
            'password_confirmation' => 'a-new-Password-123',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('ensembles.show', $ensemble));

    $login->refresh();
    expect($login->username)->toBe('band')
        ->and($login->email)->toBeNull()
        ->and($login->role)->toBe(UserRole::Ensemble)
        ->and(Hash::check('a-new-Password-123', $login->password))->toBeTrue();
});

test('leaving the password empty keeps the current one', function () {
    $ensemble = Ensemble::factory()->create();
    $login = $ensemble->createSharedLogin();
    $password = $login->password;

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.login.update', $ensemble), ['username' => 'band'])
        ->assertSessionHasNoErrors();

    expect($login->fresh()->password)->toBe($password)
        ->and($login->fresh()->username)->toBe('band');
});

test('an ensemble without a shared login gets one when its credentials are first saved', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.login.update', $ensemble), ['username' => 'band'])
        ->assertSessionHasErrors('password');

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.login.update', $ensemble), ['username' => 'band', 'password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123'])
        ->assertSessionHasNoErrors();

    expect($ensemble->sharedLogins()->sole()->username)->toBe('band');
});

test('shared login credentials are validated', function (array $input, string $error) {
    make_user(UserRole::Member, ['username' => 'taken']);
    $ensemble = Ensemble::factory()->create();
    $login = $ensemble->createSharedLogin();

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.login.update', $ensemble), $input + ['username' => 'band'])
        ->assertSessionHasErrors($error);

    expect($login->fresh()->username)->not->toBe('band');
})->with([
    'missing username' => [['username' => ''], 'username'],
    'taken username' => [['username' => 'taken'], 'username'],
    'unconfirmed password' => [['password' => 'a-new-Password-123', 'password_confirmation' => 'different'], 'password'],
]);

test('only admins can set the shared login', function (UserRole $role) {
    $ensemble = Ensemble::factory()->create();
    $login = $ensemble->createSharedLogin();
    $actor = make_user($role);
    join_ensemble($actor, $ensemble);

    $this->actingAs($actor)->get(route('ensembles.show', $ensemble))->assertDontSee(__('Save shared login'));
    $this->actingAs($actor)
        ->put(route('ensembles.login.update', $ensemble), ['username' => 'band'])
        ->assertForbidden();

    expect($login->fresh()->username)->not->toBe('band');
})->with([UserRole::Member, UserRole::Moderator]);

test('setting the shared login asks for the admin\'s password', function () {
    $ensemble = Ensemble::factory()->create();
    $login = $ensemble->createSharedLogin();

    $this->actingAs(make_user(UserRole::Admin))
        ->withSession(['auth.password_confirmed_at' => 0])
        ->put(route('ensembles.login.update', $ensemble), ['username' => 'band'])
        ->assertRedirect(route('password.confirm'));

    expect($login->fresh()->username)->not->toBe('band');
});
