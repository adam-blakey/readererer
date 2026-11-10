<?php

namespace App\Http\Requests;

use App\Enums\Color;
use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit.settings');
    }

    public function rules(): array
    {
        $words = array_merge(array_keys(GeneralSettings::TERMS), array_values(GeneralSettings::TERMS));

        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:'.config('app.readererer_image_max_kilobytes')],
            'remove_logo' => ['sometimes', 'boolean'],
            'favicon' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:'.config('app.readererer_image_max_kilobytes')],
            'remove_favicon' => ['sometimes', 'boolean'],
            'primary_color' => ['nullable', Rule::enum(Color::class)],
            'assume_attending' => ['sometimes', 'boolean'],
            'allow_change_to_unknown' => ['sometimes', 'boolean'],
            'repeating_headings' => ['sometimes', 'boolean'],
            'depping' => ['sometimes', 'boolean'],
            'hide_past_dates' => ['sometimes', 'boolean'],
            'setup_groups' => ['sometimes', 'boolean'],
            'van_drivers' => ['sometimes', 'boolean'],
            'single_ensemble' => ['sometimes', 'boolean', Rule::when(Ensemble::count() > 1, 'declined')],
            'log_emails' => ['sometimes', 'boolean'],
            'analytics' => ['sometimes', 'boolean'],
            'reminder_hours' => ['nullable', 'integer', 'min:0', 'max:720'],
            'reminder_minutes' => ['nullable', 'integer', 'min:0', 'max:59'],
            'concert_reminder_hours' => ['nullable', 'integer', 'min:0', 'max:720'],
            'concert_reminder_minutes' => ['nullable', 'integer', 'min:0', 'max:59'],
            'after_reminders' => ['required', Rule::in(GeneralSettings::AFTER_REMINDERS)],
            'image_max_kilobytes' => ['required', 'integer', 'min:1', 'max:10240'],
            'terminology' => ['sometimes', 'array:'.implode(',', $words)],
            'terminology.*' => ['nullable', 'string', 'max:50'],
            'social_links' => ['sometimes', 'array:'.implode(',', array_keys(GeneralSettings::SOCIAL_NETWORKS))],
            'social_links.*' => ['nullable', 'string', 'max:255', 'url:https'],
            'shown_user_fields' => ['sometimes', 'array'],
            'shown_user_fields.*' => [Rule::in(array_keys(User::OPTIONAL_FIELDS))],
            'google_login' => ['sometimes', 'boolean'],
            'google_client_id' => ['nullable', 'string', 'max:255', 'required_if_accepted:google_login'],
            'google_client_secret' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $this->boolean('google_login') && blank(app(GeneralSettings::class)->google_client_secret))],
            'google_register_domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+(\.[a-z0-9-]+)+$/i', 'required_with:google_register_role'],
            'google_register_role' => ['nullable', Rule::enum(UserRole::class)->except(UserRole::Ensemble), 'required_with:google_register_domain'],
        ];
    }

    public function messages(): array
    {
        return [
            'google_register_domain.regex' => __('Enter a domain name such as example.org, without the @.'),
            'single_ensemble.declined' => __('Single-ensemble mode needs a site with at most one ensemble.'),
        ];
    }
}
