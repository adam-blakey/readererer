<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateEnsembleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $login = $this->route('ensemble')->sharedLogins()->first();

        return [
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($login)],
            'password' => [Rule::requiredIf($login === null), 'nullable', 'confirmed', Password::defaults()],
        ];
    }
}
