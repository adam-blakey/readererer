<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('analytics_events')->delete();

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropColumn(['method', 'route', 'status', 'duration_ms']);
        });

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->json('attributes')->after('name');
        });
    }

    public function down(): void
    {
        DB::table('analytics_events')->delete();

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropColumn(['name', 'attributes']);
        });

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('method', 10)->after('id');
            $table->string('route')->nullable()->after('method');
            $table->unsignedSmallInteger('status')->after('route');
            $table->unsignedInteger('duration_ms')->nullable()->after('status');
        });
    }
};
