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
