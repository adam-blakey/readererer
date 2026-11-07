<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    public const CONFIG_KEYS = [
        'name' => ['app.name', 'mail.from.name'],
        'logo' => ['app.readererer_logo'],
        'favicon' => ['app.readererer_favicon'],
        'primary_color' => ['app.readererer_primary_color'],
        'assume_attending' => ['app.readererer_assume_attending'],
        'allow_change_to_unknown' => ['app.readererer_allow_change_to_unknown'],
        'repeating_headings' => ['app.readererer_repeating_headings'],
        'image_max_kilobytes' => ['app.readererer_image_max_kilobytes'],
        'terminology' => ['app.readererer_terminology'],
        'shown_user_fields' => ['app.readererer_shown_user_fields'],
        'setup_groups' => ['app.readererer_setup_groups'],
        'van_drivers' => ['app.readererer_van_drivers'],
        'single_ensemble' => ['app.readererer_single_ensemble'],
        'log_emails' => ['app.readererer_log_emails'],
        'reminder_hours' => ['app.readererer_reminder_hours'],
        'after_reminders' => ['app.readererer_after_reminders'],
        'google_login' => ['app.readererer_google_login'],
        'google_client_id' => ['services.google.client_id'],
        'google_client_secret' => ['services.google.client_secret'],
        'google_register_domain' => ['app.readererer_google_register_domain'],
        'google_register_role' => ['app.readererer_google_register_role'],
    ];

    public const ENCRYPTED = ['google_client_secret'];

    public const AFTER_REMINDERS = ['nothing', 'follow_up', 'lock'];

    public const TERMS = [
        'rehearsal' => 'rehearsals',
        'concert' => 'concerts',
        'ensemble' => 'ensembles',
        'term' => 'terms',
        'member' => 'members',
        'setup group' => 'setup groups',
        'van driver' => 'van drivers',
    ];

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function applyToConfig(): void
    {
        try {
            $settings = static::pluck('value', 'key');
        } catch (QueryException|SQLiteDatabaseDoesNotExistException) {
            return; // Not migrated yet, e.g. during the first deploy.
        }

        foreach ($settings->only(array_keys(self::CONFIG_KEYS)) as $key => $value) {
            if (in_array($key, ['logo', 'favicon'], true)) {
                $value = Storage::disk('public')->url($value);
            }

            if (in_array($key, self::ENCRYPTED, true)) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (DecryptException) {
                    continue; // Encrypted with a previous APP_KEY.
                }
            }

            foreach (self::CONFIG_KEYS[$key] as $config_key) {
                config([$config_key => $value]);
            }
        }
    }

    public static function put(string $key, mixed $value): void
    {
        if (blank($value)) {
            static::where('key', $key)->delete();
        } else {
            static::updateOrCreate(['key' => $key], ['value' => in_array($key, self::ENCRYPTED, true) ? Crypt::encryptString($value) : $value]);
        }
    }
}
