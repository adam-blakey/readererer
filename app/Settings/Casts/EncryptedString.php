<?php

namespace App\Settings\Casts;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class EncryptedString implements SettingsCast
{
    public function get($payload): ?string
    {
        try {
            return $payload === null ? null : Crypt::decryptString($payload);
        } catch (DecryptException) {
            return null; // Encrypted with a previous APP_KEY.
        }
    }

    public function set($payload): ?string
    {
        return $payload === null ? null : Crypt::encryptString($payload);
    }
}
