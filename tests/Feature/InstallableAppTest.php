<?php

use App\Features\InstallableApp;
use Laravel\Pennant\Feature;

function enable_installable_app(): void
{
    config(['app.readererer_feature_flags' => [InstallableApp::class => ['enabled' => true]]]);
    Feature::flushCache();
}

test('the manifest is a 404 and the layout links nothing while the flag is off', function () {
    $this->get('/manifest.webmanifest')->assertNotFound();
    $this->get('/login')->assertOk()->assertDontSee('rel="manifest"', false)->assertDontSee('serviceWorker', false);
});

test('the manifest describes the site when the flag is on', function () {
    enable_installable_app();
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

test('the layout links the manifest and registers the worker when the flag is on', function () {
    enable_installable_app();

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
