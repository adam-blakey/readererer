<?php

use App\Models\InstrumentFamily;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Part;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_ensemble', function (Blueprint $table) {
            $table->foreignIdFor(InstrumentFamily::class);
            $table->integer('seat_column')->nullable();
            $table->integer('seat_row')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['instrument_family_id']);
            $table->dropColumn('seat_column');
            $table->dropColumn('seat_row');
        });
    }
};