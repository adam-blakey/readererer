<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'name' => ['required', 'string', 'max:255'],
            'seating_plan_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
