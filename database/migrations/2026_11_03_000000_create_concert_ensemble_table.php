<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $concerts = DB::table('term_dates')
            ->whereNotNull('concert_ensemble_id')
            ->get(['id as term_date_id', 'concert_ensemble_id as ensemble_id'])
            ->map(fn ($row) => (array) $row);

        Schema::table('term_dates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('concert_ensemble_id');
        });

        Schema::create('concert_ensemble', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_date_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ensemble_id')->constrained()->cascadeOnDelete();
            $table->unique(['term_date_id', 'ensemble_id']);
        });

        foreach ($concerts->chunk(500) as $chunk) {
            DB::table('concert_ensemble')->insert($chunk->values()->all());
        }
    }

    public function down(): void
    {
        $firstEnsembles = DB::table('concert_ensemble')
            ->groupBy('term_date_id')
            ->selectRaw('term_date_id, min(ensemble_id) as ensemble_id')
            ->pluck('ensemble_id', 'term_date_id');

        Schema::dropIfExists('concert_ensemble');

        Schema::table('term_dates', function (Blueprint $table) {
            $table->foreignId('concert_ensemble_id')->nullable()->constrained('ensembles');
        });

        foreach ($firstEnsembles as $termDateId => $ensembleId) {
            DB::table('term_dates')->where('id', $termDateId)->update(['concert_ensemble_id' => $ensembleId]);
        }
    }
};
