<?php

use App\Models\Ensemble;
use App\Models\TermDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the concert ensemble migration copies each concert\'s ensemble into the pivot and back', function () {
    $migration = require database_path('migrations/2026_11_03_000000_create_concert_ensemble_table.php');
    $ensemble = Ensemble::factory()->create();
    $concert = make_term_date();
    $rehearsal = make_term_date();

    $migration->down();
    DB::table('term_dates')->where('id', $concert->id)->update(['concert_ensemble_id' => $ensemble->id]);

    $migration->up();

    expect(Schema::hasColumn('term_dates', 'concert_ensemble_id'))->toBeFalse();
    expect(TermDate::find($concert->id)->concert_ensembles->modelKeys())->toBe([$ensemble->id]);
    expect(TermDate::find($rehearsal->id)->is_concert)->toBeFalse();

    $migration->down();

    expect(DB::table('term_dates')->pluck('concert_ensemble_id', 'id')->all())
        ->toBe([$concert->id => $ensemble->id, $rehearsal->id => null]);

    $migration->up();
});
