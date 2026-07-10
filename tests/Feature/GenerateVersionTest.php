<?php

// app:generate-version writes version.json at build time; config/_version.php
// reads it back when the app boots. These tests swap the real version.json
// out of the way and restore it afterwards.

beforeEach(function () {
    $this->versionPath = base_path('version.json');
    $this->originalVersionJson = is_file($this->versionPath)
        ? file_get_contents($this->versionPath)
        : null;
});

afterEach(function () {
    if ($this->originalVersionJson === null) {
        @unlink($this->versionPath);
    } else {
        file_put_contents($this->versionPath, $this->originalVersionJson);
    }
});

test('app:generate-version writes the provided metadata to version.json', function () {
    $this->artisan('app:generate-version', [
        '--tag' => 'v1.2.3',
        '--hash' => 'abc1234',
        '--date' => '2026-01-02T03:04:05+00:00',
    ])->assertSuccessful();

    expect(json_decode(file_get_contents($this->versionPath), true))->toBe([
        'tag' => 'v1.2.3',
        'hash' => 'abc1234',
        'date' => '2026-01-02T03:04:05+00:00',
    ]);
});

test('config/_version.php reads version.json without running git', function () {
    file_put_contents($this->versionPath, json_encode([
        'tag' => 'v1.2.3',
        'hash' => 'abc1234',
        'date' => '2026-01-02T03:04:05+00:00',
    ]));

    $config = require base_path('config/_version.php');

    expect($config['tag'])->toBe('1.2.3')
        ->and($config['hash'])->toBe('abc1234')
        ->and($config['date']->toIso8601String())->toBe('2026-01-02T03:04:05+00:00')
        ->and($config['string'])->toBe('1.2.3-abc1234 (02/01/26 03:04)');
});

test('config/_version.php falls back to placeholders when version.json is missing', function () {
    @unlink($this->versionPath);

    $config = require base_path('config/_version.php');

    expect($config['tag'])->toBe('-.-.-')
        ->and($config['hash'])->toBe('')
        ->and($config['date'])->toBeNull();
});

test('config/_version.php ignores empty values in version.json', function () {
    file_put_contents($this->versionPath, json_encode([
        'tag' => '',
        'hash' => '',
        'date' => '',
    ]));

    $config = require base_path('config/_version.php');

    expect($config['tag'])->toBe('-.-.-');
});
