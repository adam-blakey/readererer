<?php

use App\Enums\UserRole;
use App\Models\Composer;
use Illuminate\Support\Facades\Gate;

beforeEach(fn () => Gate::before(fn () => true));

test('the composer index can include archived records', function () {
    Composer::factory()->create(['first_name' => 'Johannes', 'last_name' => 'Brahms']);
    $trashed = Composer::factory()->create(['first_name' => 'Ottorino', 'last_name' => 'Respighi']);
    $trashed->delete();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('composers.index'))
        ->assertOk()->assertSee('Brahms')->assertDontSee('Respighi');

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('composers.index', ['with_trashed' => 1]))
        ->assertOk()->assertSee('Brahms')->assertSee('Respighi');
});

test('a deleted composer is archived and can be restored', function () {
    $admin = make_user(UserRole::Admin);
    $composer = Composer::factory()->create(['first_name' => 'Johannes', 'last_name' => 'Brahms']);

    $this->actingAs($admin)->delete(route('composers.destroy', $composer))->assertRedirect();
    expect($composer->fresh()->trashed())->toBeTrue();

    $this->actingAs($admin)->patch(route('composers.restore', $composer->id))->assertRedirect();
    expect($composer->fresh()->trashed())->toBeFalse();
});
