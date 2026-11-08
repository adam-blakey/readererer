<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

abstract class FeatureFlag
{
    public const DEFAULTS = [
        'enabled' => false,
        'roles' => [],
        'ensembles' => [],
        'starts_at' => null,
        'from_version' => null,
    ];

    abstract public function title(): string;

    abstract public function description(): string;

    public static function options(): array
    {
        return (config('app.readererer_feature_flags')[static::class] ?? []) + self::DEFAULTS;
    }

    public static function releaseNumber(string $version): string
    {
        return ltrim(strtok($version, '-+'), 'vV');
    }

    public function resolve(?User $user): bool
    {
        $options = static::options();

        return $options['enabled']
            && (! $options['starts_at'] || now()->gte(Carbon::parse($options['starts_at'])))
            && (! $options['from_version'] || version_compare(self::releaseNumber(config('app.version')), self::releaseNumber($options['from_version']), '>='))
            && ($options['roles'] === [] || in_array($user?->role->value, $options['roles'], true))
            && ($options['ensembles'] === [] || $user?->ensembles->pluck('id')->intersect($options['ensembles'])->isNotEmpty());
    }
}
