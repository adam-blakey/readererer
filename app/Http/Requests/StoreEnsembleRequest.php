<?php

namespace App\Http\Requests;

use App\Models\Ensemble;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEnsembleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail) {
                if (Ensemble::withTrashed()->where('slug', Str::slug($value, '_'))->exists()) {
                    $fail(__('An ensemble with this name already exists.'));
                }
            }],
            'admins' => ['nullable', 'array'],
            'admins.*' => ['integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }
}
