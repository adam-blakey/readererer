<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Setting;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\User;

test('the feature switches are saved with the settings', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('settings.update'), [
            'name' => 'Riverside Band',
            'image_max_kilobytes' => 2048,
            'after_reminders' => 'nothing',
            'setup_groups' => '0',
            'van_drivers' => '0',
            'single_ensemble' => '1',
        ])
        ->assertSessionHasNoErrors();

    Setting::applyToConfig();

    expect(config('app.readererer_setup_groups'))->toBeFalse()
        ->and(config('app.readererer_van_drivers'))->toBeFalse()
        ->and(config('app.readererer_single_ensemble'))->toBeTrue();
});

test('single-ensemble mode cannot be turned on while there are several ensembles', function () {
    Ensemble::factory()->count(2)->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('settings.update'), [
            'name' => 'Riverside Band',
            'image_max_kilobytes' => 2048,
            'after_reminders' => 'nothing',
            'single_ensemble' => '1',
        ])
        ->assertSessionHasErrors('single_ensemble');
});

test('with setup groups off their pages are closed and the term dates table hides them', function () {
    config(['app.readererer_setup_groups' => false]);
    $admin = make_user(UserRole::Admin);
    $setupGroup = SetupGroup::factory()->create(['name' => 'Group Hidden']);
    $termDate = make_term_date();
    $termDate->update(['setup_group_id' => $setupGroup->id]);

    $this->actingAs($admin)->get(route('setupgroups.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('setupgroups.show', $setupGroup))->assertForbidden();
    $this->actingAs($admin)->post(route('term-dates.send-setup-reminder', $termDate))->assertNotFound();

    $this->actingAs($admin)->get(route('terms.show', $termDate->term))
        ->assertOk()
        ->assertDontSee(__('Setup group'))
        ->assertDontSee(__('Resend setup reminder'));
});

test('with setup groups off saving a user or a term keeps their setup groups', function () {
    config(['app.readererer_setup_groups' => false]);
    $admin = make_user(UserRole::Admin);
    $setupGroup = SetupGroup::factory()->create();
    $member = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
    $termDate = make_term_date(Term::factory()->create());
    $termDate->update(['setup_group_id' => $setupGroup->id]);

    $this->actingAs($admin)->patch(route('users.update', $member), [
        'first_name' => $member->first_name,
        'last_name' => $member->last_name,
        'email' => $member->email,
        'role' => UserRole::Member->value,
    ])->assertSessionHasNoErrors();

    $this->actingAs($admin)->patch(route('terms.update', $termDate->term), [
        'name' => $termDate->term->name,
        'slug' => $termDate->term->slug,
        'term_dates' => [[
            'id' => $termDate->id,
            'start_datetime' => $termDate->start_datetime->format('Y-m-d H:i:s'),
            'end_datetime' => $termDate->end_datetime->format('Y-m-d H:i:s'),
        ]],
    ])->assertSessionHasNoErrors();

    expect($member->fresh()->setup_group_id)->toBe($setupGroup->id)
        ->and($termDate->fresh()->setup_group_id)->toBe($setupGroup->id);
});

test('with van drivers off the reminder is closed and a setup group keeps its drivers', function () {
    config(['app.readererer_van_drivers' => false]);
    $admin = make_user(UserRole::Admin);
    $driver = make_user();
    $setupGroup = SetupGroup::factory()->create();
    $setupGroup->van_drivers()->attach($driver);
    $termDate = make_term_date();
    $termDate->update(['van_driver_id' => $driver->id]);

    $this->actingAs($admin)->post(route('term-dates.send-van-driver-reminder', $termDate))->assertNotFound();

    $this->actingAs($admin)->get(route('setupgroups.edit', $setupGroup))
        ->assertOk()
        ->assertDontSee('name="van_drivers[]"', false);

    $this->actingAs($admin)->patch(route('setupgroups.update', $setupGroup), [
        'name' => $setupGroup->name,
        'color' => $setupGroup->color->value,
    ])->assertSessionHasNoErrors();

    expect($setupGroup->fresh()->van_drivers->pluck('id')->all())->toBe([$driver->id]);
});

test('in single-ensemble mode a new user joins the ensemble', function () {
    config(['app.readererer_single_ensemble' => true]);
    $ensemble = Ensemble::factory()->create();
    $family = make_instrument_family('Brass');
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('users.create'))
        ->assertOk()
        ->assertSee('name="instrument_family"', false);

    $this->actingAs($admin)->post(route('users.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'role' => UserRole::Member->value,
    ])->assertSessionHasErrors('instrument_family');

    $this->actingAs($admin)->post(route('users.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'role' => UserRole::Member->value,
        'instrument_family' => $family->id,
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'ada@example.com')->first();
    expect($user->ensembles->pluck('id')->all())->toBe([$ensemble->id])
        ->and($user->ensembles->first()->pivot->instrument_family_id)->toBe($family->id);
});

test('in single-ensemble mode a second ensemble cannot be created', function () {
    config(['app.readererer_single_ensemble' => true]);
    Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.create'))
        ->assertForbidden();
});
