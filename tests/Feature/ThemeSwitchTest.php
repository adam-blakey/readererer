<?php

use App\Enums\UserRole;

test('the header has a light and dark mode switch', function () {

    $response = $this->actingAs(make_user(UserRole::Member))->get('/dashboard')
        ->assertOk()
        ->assertSee('data-theme-toggle', false)
        ->assertSee("localStorage.getItem('theme')", false);

    preg_match("/'nonce-([^']+)'/", $response->headers->get('Content-Security-Policy'), $nonce);
    $response->assertSee('<script nonce="'.$nonce[1].'">', false);
});
