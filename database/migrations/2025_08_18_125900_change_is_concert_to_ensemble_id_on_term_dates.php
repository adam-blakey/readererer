<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            if (! Schema::hasColumn('term_dates', 'concert_ensemble_id')) {
                $table->foreignId('concert_ensemble_id')->nullable()->constrained('ensembles');
            }

            if (Schema::hasColumn('term_dates', 'is_concert')) {
                $table->dropColumn('is_concert');
            }
        });
    }

    public function down(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            if (! Schema::hasColumn('term_dates', 'is_concert')) {
                $table->boolean('is_concert')->default(false);
            }

            if (Schema::hasColumn('term_dates', 'concert_ensemble_id')) {
                try {
                    $table->dropConstrainedForeignId('concert_ensemble_id');
                } catch (Throwable $e) {
                    $table->dropForeign(['concert_ensemble_id']);
                    $table->dropColumn('concert_ensemble_id');
                }
            }
        });
    }
};
