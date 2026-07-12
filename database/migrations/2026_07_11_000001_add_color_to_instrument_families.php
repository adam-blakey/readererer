<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('instrument_families', function (Blueprint $table) {
            $table->string('color')->default('blue');
        });

        // Preserve the colours previously derived from the record id.
        $colors = ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan'];
        DB::table('instrument_families')->orderBy('id')->pluck('id')->each(function ($id) use ($colors) {
            DB::table('instrument_families')->where('id', $id)->update([
                'color' => $colors[($id - 1) % count($colors)],
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument_families', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
