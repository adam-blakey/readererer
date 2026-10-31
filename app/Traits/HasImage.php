<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * For a model whose `image` column holds either an external URL (as the
 * seeders produce) or the path of an upload on the `public` disk.
 */
trait HasImage
{
    protected static function bootHasImage(): void
    {
        static::updated(function ($model) {
            $previous = $model->getOriginal('image');

            if ($previous !== $model->image && static::isStoredImage($previous)) {
                Storage::disk('public')->delete($previous);
            }
        });
    }

    public static function imageDirectory(): string
    {
        return 'images/'.(new static)->getTable();
    }

    public static function isStoredImage(?string $image): bool
    {
        return filled($image) && ! Str::startsWith($image, ['http://', 'https://', '//', '/']);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return static::isStoredImage($this->image)
            ? Storage::disk('public')->url($this->image)
            : $this->image;
    }

    public function applyImageInput(?UploadedFile $file, bool $remove = false): void
    {
        if ($file) {
            $this->image = $file->store(static::imageDirectory(), 'public');
        } elseif ($remove) {
            $this->image = null;
        }
    }
}
