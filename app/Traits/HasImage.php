<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// `image` holds either an external URL or the path of an upload on the public disk.
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

    public static function isStoredImage(?string $image): bool
    {
        return filled($image) && ! Str::isUrl($image);
    }

    public function getImageUrlAttribute(): ?string
    {
        return static::isStoredImage($this->image) ? Storage::disk('public')->url($this->image) : $this->image;
    }

    public function applyImageInput(?UploadedFile $file, bool $remove = false): void
    {
        if ($file) {
            $this->image = $file->store('images/'.$this->getTable(), 'public');
        } elseif ($remove) {
            $this->image = null;
        }
    }
}
