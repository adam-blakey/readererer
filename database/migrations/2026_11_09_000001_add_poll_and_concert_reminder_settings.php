<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PROPERTIES = ['hide_past_dates', 'concert_reminder_hours'];

    public function up(): void
    {
        DB::table('settings')->insert(array_map(fn ($name) => [
            'group' => 'general',
            'name' => $name,
            'payload' => 'null',
        ], self::PROPERTIES));
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'general')->whereIn('name', self::PROPERTIES)->delete();
    }
};
