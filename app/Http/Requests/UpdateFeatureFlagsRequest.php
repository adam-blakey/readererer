<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeatureFlagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit.settings');
    }

    public function rules(): array
    {
        return [
            'flags' => ['sometimes', 'array'],
            'flags.*.enabled' => ['sometimes', 'boolean'],
            'flags.*.roles' => ['sometimes', 'array'],
            'flags.*.roles.*' => [Rule::enum(UserRole::class)],
            'flags.*.ensembles' => ['sometimes', 'array'],
            'flags.*.ensembles.*' => ['integer', Rule::exists('ensembles', 'id')],
            'flags.*.starts_at' => ['nullable', 'date'],
            'flags.*.from_version' => ['nullable', 'regex:/^v?\d+(\.\d+){0,2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'flags.*.starts_at.date' => __('Enter a valid date and time.'),
            'flags.*.from_version.regex' => __('Enter a release such as v1.5.0.'),
        ];
    }
}
