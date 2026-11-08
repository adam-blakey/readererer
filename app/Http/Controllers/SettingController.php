<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'page_name' => __('Settings'),
            'has_uploaded_logo' => Setting::where('key', 'logo')->exists(),
            'has_uploaded_favicon' => Setting::where('key', 'favicon')->exists(),
            'has_google_client_secret' => filled(config('services.google.client_secret')),
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        Setting::put('name', $request->validated('name'));
        Setting::put('primary_color', $request->validated('primary_color'));
        Setting::put('image_max_kilobytes', (int) $request->validated('image_max_kilobytes'));
        Setting::put('reminder_hours', (int) $request->validated('reminder_hours'));
        Setting::put('after_reminders', $request->validated('after_reminders'));
        Setting::put('terminology', array_filter($request->validated('terminology', []), 'filled'));
        // Not Setting::put(), which deletes an empty list and so would fall back to showing every field.
        Setting::updateOrCreate(['key' => 'shown_user_fields'], ['value' => $request->validated('shown_user_fields', [])]);

        Setting::put('google_client_id', $request->validated('google_client_id'));
        Setting::put('google_register_domain', strtolower((string) $request->validated('google_register_domain')));
        Setting::put('google_register_role', $request->filled('google_register_role') ? (int) $request->validated('google_register_role') : null);

        if ($request->filled('google_client_secret')) {
            Setting::put('google_client_secret', $request->validated('google_client_secret'));
        }

        foreach (['assume_attending', 'allow_change_to_unknown', 'repeating_headings', 'depping', 'setup_groups', 'van_drivers', 'single_ensemble', 'log_emails', 'google_login'] as $key) {
            Setting::put($key, $request->boolean($key));
        }

        foreach (['logo', 'favicon'] as $image) {
            $old = Setting::where('key', $image)->value('value');

            if ($request->hasFile($image) || $request->boolean('remove_'.$image)) {
                Setting::put($image, $request->file($image)?->store('images/settings', 'public'));

                if ($old) {
                    Storage::disk('public')->delete($old);
                }
            }
        }

        return redirect()->route('settings.edit')->with('status', __('Settings saved.'));
    }
}
