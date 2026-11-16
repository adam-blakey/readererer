<?php

use App\Enums\UserRole;

test('the menu links to notifications for users who can view them', function () {

    foreach (UserRole::cases() as $role) {
        $user = make_user($role);
        $response = $this->actingAs($user)->get(route('feature-flags.edit'));

        $user->can('view.notifications')
            ? $response->assertSee(route('notifications.index'), false)
            : $response->assertDontSee(route('notifications.index'), false);
    }
});

test('the notifications link is highlighted on the notifications page', function () {

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('notifications.index'))
        ->assertSeeInOrder(['nav-item active', route('notifications.index')], false);
});
