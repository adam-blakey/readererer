<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnsembleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorisation is handled by the controller's authorizeResource() call.
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('ensembles', 'short_name')->ignore($this->route('ensemble'))],
            'short_name' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('ensembles', 'short_name')->ignore($this->route('ensemble')),
                Rule::unique('ensembles', 'name')->ignore($this->route('ensemble')),
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:[_-][a-z0-9]+)*$/',
                Rule::unique('ensembles', 'slug')->ignore($this->route('ensemble')),
            ],
            'seating_plan_enabled' => ['sometimes', 'boolean'],
            'show' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:'.config('app.readererer_image_max_kilobytes')],
            'remove_image' => ['sometimes', 'boolean'],
            'admins' => ['nullable', 'array'],
            'admins.*' => ['integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => __('The slug may only contain lowercase letters, numbers, hyphens and underscores.'),
            'name.unique' => __('Another ensemble uses this as its short name.'),
            'short_name.unique' => __('Another ensemble already has this name or short name.'),
        ];
    }
}
