<?php

use App\Enums\UserRole;
use App\Features\ThemeSwitch;
use Laravel\Pennant\Feature;

test('the header has a light and dark mode switch when the flag is on', function () {
    config(['app.readererer_feature_flags' => [ThemeSwitch::class => ['enabled' => true]]]);
    Feature::flushCache();

    $response = $this->actingAs(make_user(UserRole::Member))->get('/dashboard')
        ->assertOk()
        ->assertSee('data-theme-toggle', false)
        ->assertSee("localStorage.getItem('theme')", false);

    preg_match("/'nonce-([^']+)'/", $response->headers->get('Content-Security-Policy'), $nonce);
    $response->assertSee('<script nonce="'.$nonce[1].'">', false);
});

test('the switch is hidden while the flag is off', function () {
    $this->actingAs(make_user(UserRole::Member))->get('/dashboard')
        ->assertOk()
        ->assertDontSee('data-theme-toggle', false)
        ->assertDontSee("localStorage.getItem('theme')", false);
});
