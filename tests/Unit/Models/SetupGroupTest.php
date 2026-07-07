<?php

use App\Models\SetupGroup;
use App\Models\User;

test('van drivers are ordered by their sort value', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $second = User::factory()->create();
    $first = User::factory()->create();
    $setupGroup->van_drivers()->attach($second->id, ['sort' => 2]);
    $setupGroup->van_drivers()->attach($first->id, ['sort' => 1]);

    expect($setupGroup->van_drivers->pluck('id')->all())->toBe([$first->id, $second->id]);
});

test('icon attributes resolve for annotated properties', function () {
    $setupGroup = new SetupGroup;

    expect($setupGroup->getIconForAttribute('name'))->toBe('arrow-badge-right');
    expect($setupGroup->getIconForAttribute('week'))->toBe('calendar');
    expect($setupGroup->getIconForAttribute('color'))->toBe('paint');
    expect($setupGroup->getIconForAttribute('created_at'))->toBe('pencil-bolt');
    expect($setupGroup->getIconForAttribute('updated_at'))->toBe('pencil-up');
});

test('icon attributes resolve for annotated relation methods', function () {
    expect((new SetupGroup)->getIconForAttribute('van_drivers'))->toBe('truck');
});

test('icon lookup returns null for attributes without an Icon annotation', function () {
    expect((new SetupGroup)->getIconForAttribute('nonexistent'))->toBeNull();
});

test('setup groups are soft deleted and can be restored', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);

    $setupGroup->delete();
    expect(SetupGroup::find($setupGroup->id))->toBeNull();
    expect(SetupGroup::withTrashed()->find($setupGroup->id))->not->toBeNull();

    $setupGroup->restore();
    expect(SetupGroup::find($setupGroup->id))->not->toBeNull();
});

test('setup groups persist their timestamps', function () {
    // Regression test: declared $created_at/$updated_at properties used to
    // shadow the Eloquent attributes, so timestamps were never written.
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);

    expect($setupGroup->fresh()->created_at)->not->toBeNull();
    expect($setupGroup->fresh()->updated_at)->not->toBeNull();
});
