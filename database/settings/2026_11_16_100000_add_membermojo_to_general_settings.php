<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.membermojo_shortname');
        $this->migrator->add('general.membermojo_email');
        $this->migrator->addEncrypted('general.membermojo_password');
        $this->migrator->add('general.membermojo_overwrite');
        $this->migrator->addEncrypted('general.membermojo_cookies');
        $this->migrator->add('general.membermojo_status');
    }
};
