<?php

use App\View\Components\Icon;

/*
 * <x-icon> reads public/build/icons/<name>.svg, copied there by vite.config.js;
 * if the copy lands anywhere else every icon silently renders empty.
 * Needs built assets: run `npm run build` first.
 */

dataset('used icon names', [
    'login',
    'logout',
    'pencil',
    'users',
    'mail',
    'truck',
    'calendar',
    'file-type-pdf',
]);

test('the icon component renders real SVG markup for icons used in the app', function (string $name) {
    $rendered = new Icon($name)->render();

    expect($rendered)->toBeString()
        ->and(str_contains($rendered, '<svg'))
        ->toBeTrue("Icon '{$name}' rendered no SVG. Are built assets present? "
            .'Run `npm run build` and confirm the tabler icons land at '
            .'public/build/icons/<name>.svg (see the stripBase copy target in vite.config.js).');
})->with('used icon names');

test('the icon build directory is populated at the flat path the component reads', function () {
    $iconsDir = public_path('build/icons');

    expect(is_dir($iconsDir))->toBeTrue('public/build/icons is missing — run `npm run build`.')
        ->and(file_exists($iconsDir.'/login.svg'))
        ->toBeTrue('Expected build/icons/login.svg. If the tabler icons are nested under '
            .'build/icons/node_modules/... the vite copy target is missing `rename: { stripBase: true }`.');});

test('the icon component returns an empty string for an unknown icon name', function () {
    expect(new Icon('this-icon-does-not-exist')->render())->toBe('');
});
