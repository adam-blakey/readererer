<?php

use App\Models\Composer;
use App\Models\Part;
use App\Models\Piece;
use App\Models\Setlist;

test('a composer\'s name combines first and last name', function () {
    $composer = Composer::factory()->create(['first_name' => 'Gustav', 'last_name' => 'Holst']);

    expect($composer->name)->toBe('Gustav Holst');
});

test('pieces belong to a composer', function () {
    $composer = Composer::factory()->create();
    $piece = Piece::factory()->create(['composer_id' => $composer->id]);

    expect($piece->composer->is($composer))->toBeTrue();
});

test('parts_string is empty for a piece with no parts', function () {
    $piece = Piece::factory()->create();

    expect($piece->parts_string)->toBe('');
});

test('parts_string lists the piece\'s parts comma-separated', function () {
    $piece = Piece::factory()->create();
    $instrumentFamily = make_instrument_family('Woodwind');

    foreach (['Flute', 'Oboe', 'Clarinet'] as $name) {
        Part::forceCreate([
            'piece_id' => $piece->id,
            'name' => $name,
            'instrument_family_id' => $instrumentFamily->id,
        ]);
    }

    expect($piece->fresh()->parts_string)->toBe('Flute, Oboe, Clarinet');
});

test('pieces and setlists are related through the setlist_piece pivot', function () {
    $piece = Piece::factory()->create();
    $setlist = Setlist::factory()->create();

    $setlist->pieces()->attach($piece->id);

    expect($piece->setlists()->first()->is($setlist))->toBeTrue();
});

test('pieces are soft deleted', function () {
    $piece = Piece::factory()->create();

    $piece->delete();

    expect(Piece::find($piece->id))->toBeNull();
    expect(Piece::withTrashed()->find($piece->id))->not->toBeNull();
});
