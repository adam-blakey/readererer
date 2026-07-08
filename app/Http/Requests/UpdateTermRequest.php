<?php

namespace App\Http\Requests;

use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->term);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',

            // Term dates must not be null; validate each provided row
            'term_dates' => 'array',
            'term_dates.*.id' => 'nullable|integer',
            'term_dates.*.start_datetime' => 'required|date',
            'term_dates.*.end_datetime' => 'required|date',
            'term_dates.*.ensemble_id' => 'nullable|integer|exists:ensembles,id',
            'term_dates.*.setup_group_id' => 'nullable|integer|exists:setup_groups,id',
            'term_dates.*.van_driver_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
