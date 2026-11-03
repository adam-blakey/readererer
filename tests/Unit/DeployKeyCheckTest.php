<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

function deploy_key_checks(): array
{
    return collect(['.cpanel.yml', '.github/actions/deploy-ssh/action.yml'])
        ->map(fn (string $file) => preg_match("/php -r '(.*APP_KEY.*?)'/", file_get_contents(base_path($file)), $match) ? $match[1] : null)
        ->all();
}

it('runs the same APP_KEY check in both deploy actions', function () {
    [$cpanel, $ssh] = deploy_key_checks();

    expect($cpanel)->not->toBeNull()->toBe($ssh);
});

it('passes a deploy only when .env has a valid APP_KEY', function (?string $line, bool $passes) {
    $dir = storage_path('framework/testing/deploy-key-check');
    File::ensureDirectoryExists($dir);
    File::put($dir.'/.env', "APP_NAME=Readererer\n".($line === null ? '' : $line."\n"));

    $result = Process::path($dir)->run(['php', '-r', deploy_key_checks()[0], base_path('vendor/autoload.php')]);

    File::deleteDirectory($dir);
    expect($result->successful())->toBe($passes);
})->with([
    'valid key' => ['APP_KEY=base64:'.base64_encode(str_repeat('k', 32)), true],
    'quoted valid key' => ['APP_KEY="base64:'.base64_encode(str_repeat('k', 32)).'"', true],
    'missing' => [null, false],
    'empty' => ['APP_KEY=', false],
    'no base64: prefix' => ['APP_KEY='.base64_encode(str_repeat('k', 32)), false],
    'wrong length' => ['APP_KEY=base64:'.base64_encode(str_repeat('k', 16)), false],
]);
