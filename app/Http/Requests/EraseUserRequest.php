<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EraseUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('erase', $this->route('user'));
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'confirm_name' => ['required', 'string', Rule::in([$this->route('user')->name])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'confirm_name.in' => __('The name you typed does not match this user.'),
        ];
    }
}
