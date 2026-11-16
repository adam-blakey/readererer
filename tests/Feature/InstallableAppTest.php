<?php

test('the manifest describes the site', function () {
    config(['app.name' => 'Test Orchestra', 'app.readererer_primary_color' => 'blue']);

    $this->get('/manifest.webmanifest')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJson([
            'name' => 'Test Orchestra',
            'display' => 'standalone',
            'start_url' => '/dashboard',
            'theme_color' => color_name_to_hex('blue'),
        ]);
});

test('the layout links the manifest and registers the worker', function () {

    $response = $this->get('/login')->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee("register('/sw.js')", false);

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("manifest-src 'self'")
        ->toContain("worker-src 'self'");
});

test('the service worker never caches', function () {
    expect(file_get_contents(public_path('sw.js')))->not->toContain('caches');
});
