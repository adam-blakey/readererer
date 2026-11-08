<?php

use App\Enums\UserRole;
use App\Models\Composer;

test('an admin can edit and update a composer', function () {
    $composer = Composer::factory()->create(['first_name' => 'Sergei', 'last_name' => 'Rachmaninov']);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('composers.edit', $composer))
        ->assertOk()
        ->assertSee('Sergei');

    $this->actingAs($admin)
        ->patch(route('composers.update', $composer), ['first_name' => 'Sergey', 'last_name' => 'Rachmaninoff'])
        ->assertRedirect(route('composers.show', $composer));

    expect($composer->fresh()->name)->toBe('Sergey Rachmaninoff');
});

test('updating a composer requires both names', function () {
    $composer = Composer::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('composers.update', $composer), ['first_name' => ''])
        ->assertSessionHasErrors(['first_name', 'last_name']);
});

test('only admins can edit a composer', function () {
    $composer = Composer::factory()->create(['first_name' => 'Sergei']);
    $moderator = make_user(UserRole::Moderator);

    $this->actingAs($moderator)->get(route('composers.show', $composer))->assertOk();
    $this->actingAs($moderator)->get(route('composers.edit', $composer))->assertForbidden();
    $this->actingAs($moderator)
        ->patch(route('composers.update', $composer), ['first_name' => 'X', 'last_name' => 'Y'])
        ->assertForbidden();

    expect($composer->fresh()->first_name)->toBe('Sergei');
});
