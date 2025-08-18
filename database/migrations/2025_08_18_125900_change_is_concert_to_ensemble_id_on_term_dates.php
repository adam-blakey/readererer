<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Ensemble;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            // Add ensemble_id nullable and constrain to ensembles if table exists
            if (!Schema::hasColumn('term_dates', 'concert_ensemble_id')) {
                $table->foreignId('concert_ensemble_id')->nullable()->constrained('ensembles');
            }

            // Remove old boolean flag
            if (Schema::hasColumn('term_dates', 'is_concert')) {
                $table->dropColumn('is_concert');
            }
        });
    }

    public function down(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            // Restore is_concert boolean with default false
            if (!Schema::hasColumn('term_dates', 'is_concert')) {
                $table->boolean('is_concert')->default(false);
            }

            // Drop ensemble_id foreign key/column if present
            if (Schema::hasColumn('term_dates', 'concert_ensemble_id')) {
                // For portability across DBs, drop foreign key by convention then column
                try {
                    $table->dropConstrainedForeignId('concert_ensemble_id');
                } catch (\Throwable $e) {
                    // Fallback if dropConstrainedForeignId not supported
                    $table->dropForeign(['concert_ensemble_id']);
                    $table->dropColumn('concert_ensemble_id');
                }
            }
        });
    }
};
