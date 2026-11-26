<?php

use App\Enums\UserRole;
use App\Models\Composer;
use Illuminate\Support\Facades\Gate;

beforeEach(fn () => Gate::before(fn () => true));

test('pagination links keep the sort order and filters', function () {
    Composer::factory()->count(12)->create();
    $admin = make_user(UserRole::Admin);

    $html = $this->actingAs($admin)->get(route('composers.index', ['orderby' => 'last_name', 'order' => 'desc', 'with_trashed' => 1]))->assertOk()->getContent();

    expect($html)->toContain('page=2')->toContain('orderby=last_name')->toContain('order=desc')->toContain('with_trashed=1');
});

test('an allowed page size is used and carried through pagination and sort links', function () {
    Composer::factory()->count(30)->create();

    $this->actingAs(make_user(UserRole::Admin))->get(route('composers.index', ['per_page' => 25, 'orderby' => 'last_name', 'order' => 'asc']))
        ->assertOk()
        ->assertViewHas('entities', fn ($entities) => $entities->perPage() === 25)
        ->assertSee('name="per_page"', false)
        ->assertSee('per_page=25', false);
});

test('a page size outside the allow-list falls back to the default', function (string|array $value) {
    Composer::factory()->count(30)->create();

    $this->actingAs(make_user(UserRole::Admin))->get(route('composers.index', ['per_page' => $value]))
        ->assertOk()
        ->assertViewHas('entities', fn ($entities) => $entities->perPage() === 10);
})->with(['9999', '0', '-5', 'abc', '25abc', [['25']]]);

test('a chosen page size is saved for the user and that page', function () {
    Composer::factory()->count(30)->create();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('composers.index', ['per_page' => 50]))->assertOk();

    expect($admin->fresh()->setting('table_sizes'))->toBe(['composers.index' => 50]);

    $this->actingAs($admin->fresh())->get(route('composers.index'))
        ->assertViewHas('entities', fn ($entities) => $entities->perPage() === 50);
    $this->actingAs($admin->fresh())->get(route('setlists.index'))
        ->assertViewHas('entities', fn ($entities) => $entities->perPage() === 10);
    $this->actingAs(make_user(UserRole::Admin))->get(route('composers.index'))
        ->assertViewHas('entities', fn ($entities) => $entities->perPage() === 10);
});
