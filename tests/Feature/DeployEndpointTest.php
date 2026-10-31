<?php

use Illuminate\Support\Facades\Artisan;

it('is hidden when no deploy token is configured', function () {
    config(['app.deploy_token' => null]);

    $this->post('/_deploy')->assertNotFound();
});

it('is hidden from requests with the wrong token', function () {
    config(['app.deploy_token' => 'secret']);

    $this->withToken('wrong')->post('/_deploy')->assertNotFound();
});

it('migrates, re-caches and leaves maintenance mode with the right token', function () {
    config(['app.deploy_token' => 'secret']);
    foreach (['migrate', 'optimize', 'event:cache', 'queue:restart'] as $command) {
        Artisan::shouldReceive('call')->once()->with($command, Mockery::any())->andReturn(0);
    }
    Artisan::shouldReceive('call')->once()->with('up')->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('');

    $this->withToken('secret')->post('/_deploy')->assertOk();
});

it('answers during maintenance mode', function () {
    config(['app.deploy_token' => null]);
    $this->app->maintenanceMode()->activate([]);

    $this->post('/_deploy')->assertNotFound();
    $this->get('/health')->assertServiceUnavailable();

    $this->app->maintenanceMode()->deactivate();
});
