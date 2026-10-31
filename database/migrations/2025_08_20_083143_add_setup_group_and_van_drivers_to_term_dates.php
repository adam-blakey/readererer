<?php

use App\Models\SetupGroup;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            $table->foreignIdFor(SetupGroup::class)->nullable();
            $table->foreignIdFor(User::class, 'van_driver_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            $table->dropForeign(['setup_group_id', 'van_driver_id']);
        });
    }
};
