<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ensemble', function (Blueprint $table) {
            $table->string('seat_row', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_ensemble', function (Blueprint $table) {
            $table->integer('seat_row')->nullable()->change();
        });
    }
};
