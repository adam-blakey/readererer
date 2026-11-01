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
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        Setting::put('name', $request->validated('name'));
        Setting::put('image_max_kilobytes', (int) $request->validated('image_max_kilobytes'));
        Setting::put('terminology', array_filter($request->validated('terminology', []), 'filled'));

        foreach (['assume_attending', 'allow_change_to_unknown', 'repeating_headings'] as $key) {
            Setting::put($key, $request->boolean($key));
        }

        $logo = Setting::where('key', 'logo')->value('value');

        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            Setting::put('logo', $request->file('logo')?->store('images/settings', 'public'));

            if ($logo) {
                Storage::disk('public')->delete($logo);
            }
        }

        return redirect()->route('settings.edit')->with('status', __('Settings saved.'));
    }
}
