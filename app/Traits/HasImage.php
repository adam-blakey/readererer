<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Uploaded images for a model with an `image` column.
 *
 * The column holds either an external URL (the seeders point at a placeholder
 * service) or a path on the `public` disk written by an upload. Views read
 * `$model->image_url`, which resolves either form to something an <img> or a
 * CSS background can use.
 *
 * Nothing here deletes files. Replacing or clearing an image leaves the old
 * file behind for the scheduled `images:prune` command to sweep once no row
 * references it, so a failed save can never destroy the only copy.
 */
trait HasImage
{
    /**
     * Directory on the public disk holding this model's uploaded images.
     */
    public static function imageDirectory(): string
    {
        return 'images/'.(new static)->getTable();
    }

    /**
     * Whether the stored image is a file we hold rather than an external URL.
     */
    public function hasUploadedImage(): bool
    {
        return filled($this->image) && ! Str::startsWith($this->image, ['http://', 'https://', '//', '/']);
    }

    /**
     * URL for the image, or null when the model has none.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return $this->hasUploadedImage()
            ? Storage::disk('public')->url($this->image)
            : $this->image;
    }

    /**
     * Point the model at a newly uploaded file. The caller still saves.
     */
    public function storeImage(UploadedFile $file): void
    {
        $this->image = $file->store(static::imageDirectory(), 'public');
    }

    /**
     * Apply the image half of a submitted form: a new upload wins over the
     * "remove" checkbox. The caller still saves.
     */
    public function applyImageInput(?UploadedFile $file, bool $remove = false): void
    {
        if ($file) {
            $this->storeImage($file);
        } elseif ($remove) {
            $this->image = null;
        }
    }
}
