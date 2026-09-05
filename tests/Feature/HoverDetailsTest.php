<?php

use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;

/*
 * Components that show an abbreviation — a bare setup-group week number, a
 * stack of date fragments, a pair of initials — carry hover text spelling out
 * what they mean. Tabler initialises anything marked
 * data-bs-toggle="tooltip" on page load, and Bootstrap falls back to the
 * title attribute, so both have to be on the element.
 */

test('the setup group badge says which group it is on hover', function () {
    $setupGroup = SetupGroup::create([
        'name' => 'Setup group 1',
        'week' => 1,
        'color' => 'purple',
    ]);

    $rendered = $this->blade(
        '<x-setup-group-badge :setup_group="$setup_group" />',
        ['setup_group' => $setupGroup],
    );

    $rendered->assertSee('data-bs-toggle="tooltip"', false);
    $rendered->assertSee('title="Setup group 1"', false);
    $rendered->assertSee('aria-label="Setup group 1"', false);
});

test('the setup group dot says which group it is, and who is driving the van', function () {
    $setupGroup = SetupGroup::create([
        'name' => 'Setup group 2',
        'week' => 2,
        'color' => 'yellow',
    ]);

    $this->blade(
        '<x-setup-group-badge :setup_group="$setup_group" show_as_dot="true" />',
        ['setup_group' => $setupGroup],
    )->assertSee('title="Setup group 2"', false);

    $this->blade(
        '<x-setup-group-badge :setup_group="$setup_group" show_as_dot="true" show_with_van="true" />',
        ['setup_group' => $setupGroup],
    )->assertSee('title="Setup group 2 — van driver"', false);
});

test('a setup group badge tooltip can be turned off where something else explains it', function () {
    $setupGroup = SetupGroup::create([
        'name' => 'Setup group 3',
        'week' => 3,
        'color' => 'azure',
    ]);

    $this->blade(
        '<x-setup-group-badge :setup_group="$setup_group" :tooltip="false" />',
        ['setup_group' => $setupGroup],
    )->assertDontSee('data-bs-toggle="tooltip"', false);
});

test('a setup group falls back to its week number when it has no name', function () {
    $setupGroup = SetupGroup::create([
        'name' => '',
        'week' => 4,
        'color' => 'teal',
    ]);

    expect($setupGroup->label)->toBe('Setup group 4');
});

test('an avatar says whose it is on hover, and names their setup group when it shows one', function () {
    $setupGroup = SetupGroup::create([
        'name' => 'Setup group 1',
        'week' => 1,
        'color' => 'purple',
    ]);

    $member = make_user(attributes: ['setup_group_id' => $setupGroup->id]);

    $this->blade('<x-avatar :user="$user" />', ['user' => $member])
        ->assertSee('title="'.e($member->name).'"', false);

    // The badge riding on the avatar defers to the avatar's own tooltip.
    $rendered = $this->blade(
        '<x-avatar :user="$user" show_setup_group="true" />',
        ['user' => $member],
    );

    $rendered->assertSee('title="'.e($member->name).' — Setup group 1"', false);
    expect(substr_count($rendered->__toString(), 'data-bs-toggle="tooltip"'))->toBe(1);
});

test('an avatar tooltip can be turned off', function () {
    $this->blade('<x-avatar :user="$user" :tooltip="false" />', ['user' => make_user()])
        ->assertDontSee('data-bs-toggle="tooltip"', false);
});

test('a poll date heading spells the date out on hover', function () {
    $term = Term::factory()->create();
    $ensemble = Ensemble::factory()->create();
    $rehearsal = make_term_date($term);
    $concert = make_term_date($term, $ensemble);

    $rendered = $this->blade(
        '<x-attendances.heading :term_dates="$term_dates" :ensemble="$ensemble" />',
        ['term_dates' => collect([$rehearsal, $concert]), 'ensemble' => $ensemble],
    );

    $rendered->assertSee('title="Rehearsal: '.e($rehearsal->schedule_label).'"', false);
    $rendered->assertSee('title="Concert: '.e($concert->schedule_label).' ('.e($ensemble->name).')"', false);
});
