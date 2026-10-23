<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesImageUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnsembleRequest extends FormRequest
{
    use ValidatesImageUpload;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorisation is handled by the controller's authorizeResource() call.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            // The slug addresses the ensemble in attendance URLs, so keep it to
            // URL-safe characters and unique across every row, trashed included
            // (the column carries a database-level unique index).
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:[_-][a-z0-9]+)*$/',
                Rule::unique('ensembles', 'slug')->ignore($this->route('ensemble')),
            ],
            'seating_plan_enabled' => ['sometimes', 'boolean'],
        ], $this->imageUploadRules());
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => __('The slug may only contain lowercase letters, numbers, hyphens and underscores.'),
        ];
    }
}
