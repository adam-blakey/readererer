<?php

use App\Enums\UserRole;
use App\Features\NotificationsMenuItem;
use Laravel\Pennant\Feature;

function enable_notifications_menu_flag(): void
{
    config(['app.readererer_feature_flags' => [NotificationsMenuItem::class => ['enabled' => true]]]);
    Feature::flushCache();
}

test('the menu has no notifications link while the flag is off', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('feature-flags.edit'))
        ->assertDontSee(route('notifications.index'), false);
});

test('the menu links to notifications for users who can view them once the flag is on', function () {
    enable_notifications_menu_flag();

    foreach (UserRole::cases() as $role) {
        $user = make_user($role);
        $response = $this->actingAs($user)->get(route('feature-flags.edit'));

        $user->can('view.notifications')
            ? $response->assertSee(route('notifications.index'), false)
            : $response->assertDontSee(route('notifications.index'), false);
    }
});

test('the notifications link is highlighted on the notifications page', function () {
    enable_notifications_menu_flag();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('notifications.index'))
        ->assertSeeInOrder(['nav-item active', route('notifications.index')], false);
});
