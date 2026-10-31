<?php

namespace App\Http\Requests;

use App\Enums\Color;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSetupGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'week' => ['nullable', 'integer', 'min:0', 'max:10'],
            'color' => ['required', new Enum(Color::class)],
            'van_drivers' => ['nullable', 'array'],
            'van_drivers.*' => ['exists:users,id'],
        ];
    }
}
