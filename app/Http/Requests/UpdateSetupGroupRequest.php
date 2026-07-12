<?php

namespace App\Http\Requests;

use App\Enums\ColorName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateSetupGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'week' => ['nullable', 'integer', 'min:0', 'max:10'],
            'color' => ['required', new Enum(ColorName::class)],
            'van_drivers' => ['nullable', 'array'],
            'van_drivers.*' => ['exists:users,id'],
        ];
    }
}
