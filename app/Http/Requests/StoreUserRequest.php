<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(UserRole::class)->only(UserRole::assignableBy($this->user()))],
            'setup_group' => ['nullable', 'exists:setup_groups,id'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_post_code' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact_address_line1' => ['nullable', 'string', 'max:255'],
            'emergency_contact_address_line2' => ['nullable', 'string', 'max:255'],
            'emergency_contact_address_city' => ['nullable', 'string', 'max:255'],
            'emergency_contact_address_post_code' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:tomorrow'],
            'has_photo_permission' => ['sometimes', 'boolean'],
            'is_gift_aiding_subs' => ['sometimes', 'boolean'],
        ];
    }
}
