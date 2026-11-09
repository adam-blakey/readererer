<?php

namespace App\Traits;

use App\Features\ImageScaling;
use App\Http\Middleware\AddSecurityHeaders;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;

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

    public function getImageUrlAttribute(): string
    {
        if (blank($this->image)) {
            return Vite::asset('resources/images/placeholders/'.$this->getTable().'.svg');
        }

        return static::isStoredImage($this->image) ? Storage::disk('public')->url($this->image) : AddSecurityHeaders::allowImage($this->image);
    }

    public function applyImageInput(?UploadedFile $file, bool $remove = false): void
    {
        if ($file) {
            $this->image = $this->storeScaled($file) ?? $file->store('images/'.$this->getTable(), 'public');
        } elseif ($remove) {
            $this->image = null;
        }
    }

    // shortcut: GIFs are stored as uploaded to keep animation; a queued job could shrink them later.
    private function storeScaled(UploadedFile $file): ?string
    {
        $max_side = 1600;
        $type = $file->getMimeType();

        if (! Feature::active(ImageScaling::class) || ! in_array($type, ['image/jpeg', 'image/png', 'image/webp'])) {
            return null;
        }

        $contents = $file->get();
        [$width, $height] = @getimagesizefromstring($contents) ?: [0, 0];
        $source = $width ? @imagecreatefromstring($contents) : false;

        if (! $source || max($width, $height) <= $max_side) {
            return null;
        }

        if ($type === 'image/jpeg' && function_exists('exif_read_data')) {
            $angle = [3 => 180, 6 => -90, 8 => 90][@exif_read_data($file->getRealPath())['Orientation'] ?? 0] ?? 0;
            $source = $angle ? imagerotate($source, $angle, 0) : $source;
        }

        $scaled = imagescale($source, imagesx($source) >= imagesy($source) ? $max_side : (int) round(imagesx($source) * $max_side / imagesy($source)));

        ob_start();
        match ($type) {
            'image/jpeg' => imagejpeg($scaled, null, 85),
            'image/png' => imagepng($scaled),
            'image/webp' => imagewebp($scaled, null, 85),
        };
        $path = 'images/'.$this->getTable().'/'.Str::random(40).'.'.$file->guessExtension();
        Storage::disk('public')->put($path, ob_get_clean());

        return $path;
    }
}
