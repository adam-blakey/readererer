<?php

use App\Enums\UserRole;
use App\Features\TableSize;
use App\Models\Composer;
use Illuminate\Support\Facades\Gate;
use Laravel\Pennant\Feature;

beforeEach(fn () => Gate::before(fn () => true));

function enable_table_size(): void
{
    config(['app.readererer_feature_flags' => [TableSize::class => ['enabled' => true]]]);
    Feature::flushCache();
}

test('pagination links keep the sort order and filters', function () {
    Composer::factory()->count(12)->create();
    $admin = make_user(UserRole::Admin);

    $html = $this->actingAs($admin)->get(route('composers.index', ['orderby' => 'last_name', 'order' => 'desc', 'with_trashed' => 1]))->assertOk()->getContent();

    expect($html)->toContain('page=2')->toContain('orderby=last_name')->toContain('order=desc')->toContain('with_trashed=1');
});

test('the page size stays at the default while the feature is off', function () {
    Composer::factory()->count(30)->create();

    $this->actingAs(make_user(UserRole::Admin))->get(route('composers.index', ['per_page' => 25]))
        ->assertOk()
        ->assertViewHas('entities', fn ($entities) => $entities->perPage() === 10)
        ->assertDontSee('name="per_page"', false);
});

test('an allowed page size is used and carried through pagination and sort links', function () {
    enable_table_size();
    Composer::factory()->count(30)->create();

    $this->actingAs(make_user(UserRole::Admin))->get(route('composers.index', ['per_page' => 25, 'orderby' => 'last_name', 'order' => 'asc']))
        ->assertOk()
        ->assertViewHas('entities', fn ($entities) => $entities->perPage() === 25)
        ->assertSee('name="per_page"', false)
        ->assertSee('per_page=25', false);
});

test('a page size outside the allow-list falls back to the default', function (string|array $value) {
    enable_table_size();
    Composer::factory()->count(30)->create();

    $this->actingAs(make_user(UserRole::Admin))->get(route('composers.index', ['per_page' => $value]))
        ->assertOk()
        ->assertViewHas('entities', fn ($entities) => $entities->perPage() === 10);
})->with(['9999', '0', '-5', 'abc', '25abc', [['25']]]);
