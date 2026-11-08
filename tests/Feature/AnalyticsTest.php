<?php

use Laravel\Nightwatch\Core as Nightwatch;

test('nightwatch samples nothing while analytics are off', function () {
    expect(app(Nightwatch::class)->config['sampling'])->each->toBe(0.0);
});

test('nightwatch keeps its sampling rates once analytics are on', function () {
    $_SERVER['READERERER_ANALYTICS'] = 'true';

    try {
        $this->refreshApplication();
    } finally {
        unset($_SERVER['READERERER_ANALYTICS']);
    }

    expect(app(Nightwatch::class)->config['sampling'])->each->toBe(1.0);
});
