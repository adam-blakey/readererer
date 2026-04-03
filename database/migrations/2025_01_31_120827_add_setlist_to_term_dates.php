<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            $table->foreignId('setlist_id')->nullable()->after('term_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            $table->dropForeign(['setlist_id']);
            $table->dropColumn('setlist_id');
        });
    }
};
