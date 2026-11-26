<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setup_group_van_driver', function (Blueprint $table) {
            $table->nullableTimestamps();
        });
    }

    public function down(): void
    {
        Schema::table('setup_group_van_driver', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
