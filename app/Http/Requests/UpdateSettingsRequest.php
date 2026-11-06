<?php

namespace App\Http\Requests;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit.settings');
    }

    public function rules(): array
    {
        $words = array_merge(array_keys(Setting::TERMS), array_values(Setting::TERMS));

        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp,gif', 'max:'.config('app.readererer_image_max_kilobytes')],
            'remove_logo' => ['sometimes', 'boolean'],
            'assume_attending' => ['sometimes', 'boolean'],
            'allow_change_to_unknown' => ['sometimes', 'boolean'],
            'repeating_headings' => ['sometimes', 'boolean'],
            'image_max_kilobytes' => ['required', 'integer', 'min:1', 'max:10240'],
            'terminology' => ['sometimes', 'array:'.implode(',', $words)],
            'terminology.*' => ['nullable', 'string', 'max:50'],
            'hidden_user_fields' => ['sometimes', 'array'],
            'hidden_user_fields.*' => [Rule::in(array_keys(User::OPTIONAL_FIELDS))],
        ];
    }
}
