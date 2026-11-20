<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'attendances' => [['user_id', 'ensemble_id']],
        'user_ensemble' => [['ensemble_id', 'user_id'], ['user_id']],
        'term_dates' => [['term_id'], ['setup_group_id', 'start_datetime']],
        'email_logs' => [['term_date_id']],
        'email_recipients' => [['email_log_id']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $table) use ($indexes) {
                foreach ($indexes as $columns) {
                    $table->index($columns);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $table) use ($indexes) {
                foreach ($indexes as $columns) {
                    $table->dropIndex($columns);
                }
            });
        }
    }
};
