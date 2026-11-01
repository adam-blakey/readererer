<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    public const CONFIG_KEYS = [
        'name' => ['app.name', 'mail.from.name'],
        'logo' => ['app.readererer_logo'],
        'assume_attending' => ['app.readererer_assume_attending'],
        'allow_change_to_unknown' => ['app.readererer_allow_change_to_unknown'],
        'repeating_headings' => ['app.readererer_repeating_headings'],
        'image_max_kilobytes' => ['app.readererer_image_max_kilobytes'],
        'terminology' => ['app.readererer_terminology'],
    ];

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
            if ($key === 'logo') {
                $value = Storage::disk('public')->url($value);
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
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
