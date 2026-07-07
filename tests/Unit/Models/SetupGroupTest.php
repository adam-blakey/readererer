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

test('deleting a setup group soft-deletes the row but raises a warning', function () {
    // SetupGroup declares real `protected $created_at` / `$updated_at`
    // properties (for the icon annotations), which shadow Eloquent's magic
    // attribute access inside the model. The soft-delete UPDATE still runs,
    // but SoftDeletes then trips an "Undefined array key" warning syncing
    // the original attributes. This documents the current (buggy) behaviour.
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);

    expect(fn () => $setupGroup->delete())->toThrow(ErrorException::class, 'Undefined array key "updated_at"');

    expect(SetupGroup::find($setupGroup->id))->toBeNull();
    expect(SetupGroup::withTrashed()->find($setupGroup->id))->not->toBeNull();
});
