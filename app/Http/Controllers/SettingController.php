<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit(GeneralSettings $settings)
    {
        return view('settings.edit', [
            'page_name' => __('Settings'),
            'has_uploaded_logo' => filled($settings->logo),
            'has_uploaded_favicon' => filled($settings->favicon),
            'has_google_client_secret' => filled(config('services.google.client_secret')),
        ]);
    }

    public function update(UpdateSettingsRequest $request, GeneralSettings $settings)
    {
        $settings->name = $request->validated('name');
        $settings->primary_color = $request->validated('primary_color');
        $settings->image_max_kilobytes = (int) $request->validated('image_max_kilobytes');
        $settings->reminder_hours = (int) $request->validated('reminder_hours');
        $settings->after_reminders = $request->validated('after_reminders');
        $settings->terminology = array_filter($request->validated('terminology', []), 'filled') ?: null;
        $settings->shown_user_fields = $request->validated('shown_user_fields', []);

        $settings->google_client_id = $request->validated('google_client_id');
        $settings->google_register_domain = strtolower((string) $request->validated('google_register_domain')) ?: null;
        $settings->google_register_role = $request->filled('google_register_role') ? (int) $request->validated('google_register_role') : null;

        if ($request->filled('google_client_secret')) {
            $settings->google_client_secret = $request->validated('google_client_secret');
        }

        foreach (['assume_attending', 'allow_change_to_unknown', 'repeating_headings', 'depping', 'setup_groups', 'van_drivers', 'single_ensemble', 'log_emails', 'google_login'] as $key) {
            $settings->{$key} = $request->boolean($key);
        }

        foreach (['logo', 'favicon'] as $image) {
            $old = $settings->{$image};

            if ($request->hasFile($image) || $request->boolean('remove_'.$image)) {
                $settings->{$image} = $request->file($image)?->store('images/settings', 'public');

                if ($old) {
                    Storage::disk('public')->delete($old);
                }
            }
        }

        $settings->save();

        return redirect()->route('settings.edit')->with('status', __('Settings saved.'));
    }
}
