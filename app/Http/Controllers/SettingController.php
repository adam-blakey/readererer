<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestination;

class SettingController extends Controller
{
    public function edit(GeneralSettings $settings)
    {
        return view('settings.edit', [
            'page_name' => __('Settings'),
            'has_uploaded_logo' => filled($settings->logo),
            'has_uploaded_favicon' => filled($settings->favicon),
            'has_google_client_secret' => filled(config('services.google.client_secret')),
            'latest_backup' => collect(config('backup.backup.destination.disks'))
                ->map(fn ($disk) => BackupDestination::create($disk, config('backup.backup.name'))->newestBackup())
                ->filter()
                ->sortByDesc(fn ($backup) => $backup->date())
                ->first(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, GeneralSettings $settings)
    {
        $before = $settings->toArray();
        $settings->name = $request->validated('name');
        $settings->primary_color = $request->validated('primary_color');
        $settings->image_max_kilobytes = (int) $request->validated('image_max_kilobytes');
        $settings->reminder_minutes = $request->validated('reminder_hours') * 60 + $request->validated('reminder_minutes');
        $settings->concert_reminder_minutes = $request->validated('concert_reminder_hours') * 60 + $request->validated('concert_reminder_minutes');
        $settings->after_reminders = $request->validated('after_reminders');
        $settings->terminology = array_filter($request->validated('terminology', []), 'filled') ?: null;
        $settings->shown_user_fields = $request->validated('shown_user_fields', []);
        $settings->social_links = array_filter($request->validated('social_links', []), 'filled') ?: null;
        $settings->backup_time = $request->validated('backup_time');
        $settings->backup_keep_days = $request->filled('backup_keep_days') ? (int) $request->validated('backup_keep_days') : null;
        $settings->backup_email = $request->validated('backup_email');

        $settings->google_client_id = $request->validated('google_client_id');
        $settings->google_register_domain = strtolower((string) $request->validated('google_register_domain')) ?: null;
        $settings->google_register_role = $request->filled('google_register_role') ? (int) $request->validated('google_register_role') : null;

        if ($request->filled('google_client_secret')) {
            $settings->google_client_secret = $request->validated('google_client_secret');
        }

        foreach (['assume_attending', 'allow_change_to_unknown', 'repeating_headings', 'hide_past_dates', 'depping', 'setup_groups', 'van_drivers', 'single_ensemble', 'log_emails', 'analytics', 'abbreviate_surnames', 'email_previews', 'twenty_four_hour_clock', 'google_login', 'backups'] as $key) {
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

        Log::info('Site settings saved', ['changed' => array_keys(array_filter($settings->toArray(), fn ($value, $key) => $value !== ($before[$key] ?? null), ARRAY_FILTER_USE_BOTH))]);

        return redirect()->route('settings.edit')->with('status', __('Settings saved.'));
    }
}
