<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ensembles', function (Blueprint $table) {
            $table->boolean('seating_plan_enabled')->default(true)->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('ensembles', function (Blueprint $table) {
            $table->dropColumn('seating_plan_enabled');
        });
    }
};
