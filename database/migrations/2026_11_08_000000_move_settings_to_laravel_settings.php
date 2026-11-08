<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROPERTIES = [
        'name', 'logo', 'favicon', 'primary_color', 'assume_attending', 'allow_change_to_unknown', 'repeating_headings',
        'image_max_kilobytes', 'terminology', 'shown_user_fields', 'setup_groups', 'van_drivers', 'single_ensemble',
        'log_emails', 'reminder_hours', 'after_reminders', 'google_login', 'google_client_id', 'google_client_secret',
        'google_register_domain', 'google_register_role',
    ];

    public function up(): void
    {
        $values = DB::table('settings')->pluck('value', 'key');

        Schema::drop('settings');
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');
            $table->timestamps();
            $table->unique(['group', 'name']);
        });

        DB::table('settings')->insert(array_map(fn ($name) => [
            'group' => 'general',
            'name' => $name,
            'payload' => $values[$name] ?? 'null',
        ], self::PROPERTIES));
    }

    public function down(): void
    {
        $rows = DB::table('settings')->where('group', 'general')->where('payload', '!=', 'null')->get(['name', 'payload']);

        Schema::drop('settings');
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });

        DB::table('settings')->insert($rows->map(fn ($row) => [
            'key' => $row->name,
            'value' => $row->payload,
        ])->all());
    }
};
