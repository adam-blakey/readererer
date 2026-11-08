<?php

namespace App\Settings;

use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\LaravelSettings\Settings;

/**
 * A null setting falls back to its config value, which comes from .env.
 */
class GeneralSettings extends Settings
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

    public ?string $name;

    public ?string $logo;

    public ?string $favicon;

    public ?string $primary_color;

    public ?bool $assume_attending;

    public ?bool $allow_change_to_unknown;

    public ?bool $repeating_headings;

    public ?int $image_max_kilobytes;

    public ?array $terminology;

    public ?array $shown_user_fields;

    public ?bool $setup_groups;

    public ?bool $van_drivers;

    public ?bool $single_ensemble;

    public ?bool $log_emails;

    public ?int $reminder_hours;

    public ?string $after_reminders;

    public ?bool $google_login;

    public ?string $google_client_id;

    public ?string $google_client_secret;

    public ?string $google_register_domain;

    public ?int $google_register_role;

    public static function group(): string
    {
        return 'general';
    }

    public static function encrypted(): array
    {
        return ['google_client_secret'];
    }

    public static function applyToConfig(): void
    {
        try {
            $settings = app(static::class)->toArray();
        } catch (QueryException|SQLiteDatabaseDoesNotExistException|MissingSettings) {
            return; // Not migrated yet, e.g. during the first deploy.
        }

        foreach (array_filter($settings, fn ($value) => $value !== null) as $key => $value) {
            if (in_array($key, ['logo', 'favicon'], true)) {
                $value = Storage::disk('public')->url($value);
            }

            foreach (self::CONFIG_KEYS[$key] as $config_key) {
                config([$config_key => $value]);
            }
        }
    }
}
