<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_recipients', function (Blueprint $table) {
            $table->uuid('view_uuid')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('email_recipients', function (Blueprint $table) {
            $table->dropUnique(['view_uuid']);
            $table->dropColumn('view_uuid');
        });
    }
};
