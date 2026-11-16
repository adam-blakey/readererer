<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setup_groups', function (Blueprint $table) {
            $table->string('mailing_list_email')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('setup_groups', function (Blueprint $table) {
            $table->dropColumn('mailing_list_email');
        });
    }
};
