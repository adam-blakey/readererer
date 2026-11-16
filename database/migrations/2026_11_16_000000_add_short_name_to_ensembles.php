<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ensembles', function (Blueprint $table) {
            $table->string('short_name', 20)->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('ensembles', function (Blueprint $table) {
            $table->dropUnique(['short_name']);
            $table->dropColumn('short_name');
        });
    }
};
