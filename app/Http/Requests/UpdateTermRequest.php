<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->term);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $single = single_ensemble();

        return [
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('terms', 'slug')->ignore($this->term)],
            'location_id' => 'nullable|integer|exists:locations,id',
            'hidden' => 'sometimes|boolean',

            'term_dates' => 'array',
            'term_dates.*.id' => 'nullable|integer',
            'term_dates.*.start_datetime' => 'required|date',
            'term_dates.*.end_datetime' => 'required|date',
            'term_dates.*.ensemble_ids' => 'array',
            'term_dates.*.ensemble_ids.*' => ['integer', 'exists:ensembles,id', ...($single ? [Rule::in([$single->id])] : [])],
            'term_dates.*.setup_group_id' => 'nullable|integer|exists:setup_groups,id',
            'term_dates.*.van_driver_id' => 'nullable|integer|exists:users,id',
            'term_dates.*.time_tbc' => 'sometimes|boolean',
            'term_dates.*.location_id' => 'nullable|integer|exists:locations,id',
            'term_dates.*.description' => 'nullable|string|max:5000',
            'term_dates.*.concert_order' => 'nullable|string|max:5000',
            'term_dates.*.dress_code_id' => 'nullable|integer|exists:dress_codes,id',
        ];
    }
}
