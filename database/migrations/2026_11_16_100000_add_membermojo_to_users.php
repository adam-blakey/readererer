<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('membermojo_id')->nullable()->unique();
            $table->date('member_since')->nullable();
            $table->date('membership_expires_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['membermojo_id']);
            $table->dropColumn(['membermojo_id', 'member_since', 'membership_expires_on']);
        });
    }
};
