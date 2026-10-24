<?php

use Illuminate\Support\Facades\DB;

it('reports healthy when the database is reachable', function () {
    $this->get('/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok', 'database' => 'up']);
});

it('reports unavailable when the database cannot be queried', function () {
    DB::shouldReceive('connection')->andThrow(new RuntimeException('down'));

    $this->get('/health')
        ->assertStatus(503)
        ->assertExactJson(['status' => 'error', 'database' => 'down']);
});
