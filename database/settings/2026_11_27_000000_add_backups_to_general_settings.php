<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.backups');
        $this->migrator->add('general.backup_time');
        $this->migrator->add('general.backup_keep_days');
        $this->migrator->add('general.backup_email');
    }
};
