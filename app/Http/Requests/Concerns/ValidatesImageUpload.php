<?php

namespace App\Http\Requests\Concerns;

/**
 * Validation rules for the shared <x-forms.image-upload> field, so every form
 * that offers an image upload accepts the same formats and size.
 */
trait ValidatesImageUpload
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function imageUploadRules(): array
    {
        return [
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:'.config('app.readererer_image_max_kilobytes')],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
