<?php

use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Build-time version information
|--------------------------------------------------------------------------
|
| version.json is produced by the build process — `php artisan
| app:generate-version` runs from composer's post-autoload-dump hook (local
| installs and Envoy deploys), and the Dockerfile bakes the file in from
| build arguments supplied by the GitHub Actions workflows. No git commands
| are executed while the application is running; when the file is missing
| (e.g. a fresh checkout) the placeholders below are used.
*/

$version = [
    'tag' => '-.-.-',
    'hash' => '',
    'date' => '',
];

$path = dirname(__DIR__).'/version.json';

if (is_file($path)) {
    $generated = json_decode((string) file_get_contents($path), true);

    if (is_array($generated)) {
        $version = array_replace($version, array_filter(
            array_intersect_key($generated, $version),
            fn ($value) => is_string($value) && $value !== ''
        ));
    }
}

if (str_starts_with($version['tag'], 'v')) {
    $version['tag'] = substr($version['tag'], 1);
}

try {
    $date = $version['date'] !== '' ? Carbon::parse($version['date']) : null;
} catch (Exception) {
    $date = null;
}

return [
    'tag' => $version['tag'],
    'hash' => $version['hash'],
    'date' => $date,
    'string' => sprintf('%s-%s (%s)', $version['tag'], $version['hash'], $date?->format('d/m/y H:i')),
];
