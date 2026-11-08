<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['reminder', 'concert_reminder'] as $name) {
            $this->migrator->rename("general.{$name}_hours", "general.{$name}_minutes");
            $this->migrator->update("general.{$name}_minutes", fn (?int $hours) => $hours === null ? null : $hours * 60);
        }
    }

    public function down(): void
    {
        foreach (['reminder', 'concert_reminder'] as $name) {
            $this->migrator->update("general.{$name}_minutes", fn (?int $minutes) => $minutes === null ? null : intdiv($minutes, 60));
            $this->migrator->rename("general.{$name}_minutes", "general.{$name}_hours");
        }
    }
};
