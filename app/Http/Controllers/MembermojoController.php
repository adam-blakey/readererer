<?php

namespace App\Http\Controllers;

use App\Services\MembermojoClient;
use App\Services\MembermojoException;
use App\Services\MembermojoImporter;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MembermojoController extends Controller
{
    public function create(Request $request, GeneralSettings $settings)
    {
        return view('settings.membermojo', [
            'page_name' => __('Import from Membermojo'),
            'preview' => $request->session()->get('membermojo_import'),
            'summary' => $request->session()->get('membermojo_import_summary'),
            'configured' => MembermojoClient::configured(),
            'status' => $settings->membermojo_status,
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate(['export' => ['required', 'file', 'max:5120']]);

        $rows = MembermojoImporter::parse($request->file('export')->get());
        $options = ['overwrite' => $request->boolean('overwrite'), 'archive_leavers' => $request->boolean('archive_leavers')];

        $this->forgetPendingImport($request);
        $report = (new MembermojoImporter($rows, $options['overwrite'], $options['archive_leavers']))->preview();
        $path = $request->file('export')->store('imports', 'local');
        $request->session()->put('membermojo_import', ['path' => $path, 'report' => $report] + $options);

        return redirect()->route('settings.membermojo');
    }

    public function store(Request $request)
    {
        $pending = $request->session()->get('membermojo_import');

        if (! $pending || ! Storage::disk('local')->exists($pending['path'])) {
            throw ValidationException::withMessages(['export' => __('Upload the file again.')]);
        }

        try {
            $rows = MembermojoImporter::parse(Storage::disk('local')->get($pending['path']));
            $summary = (new MembermojoImporter($rows, $pending['overwrite'], $pending['archive_leavers']))->import();
            Log::info('Membermojo import finished', ['counts' => $summary['counts'], 'warnings' => count($summary['warnings'])]);
        } finally {
            $this->forgetPendingImport($request);
        }

        return redirect()->route('settings.membermojo')->with('membermojo_import_summary', $summary);
    }

    public function update(Request $request, GeneralSettings $settings)
    {
        $validated = $request->validate([
            'membermojo_shortname' => ['nullable', 'required_with:membermojo_email', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'membermojo_email' => ['nullable', 'required_with:membermojo_shortname', 'email', 'max:255'],
            'membermojo_password' => ['nullable', 'string', 'max:255'],
            'membermojo_time' => ['nullable', 'date_format:H:i'],
        ]);

        $before = [$settings->membermojo_shortname, $settings->membermojo_email, $settings->membermojo_password];
        $settings->membermojo_shortname = $validated['membermojo_shortname'] ?? null;
        $settings->membermojo_email = $validated['membermojo_email'] ?? null;
        $settings->membermojo_overwrite = $request->boolean('membermojo_overwrite');
        $settings->membermojo_time = $validated['membermojo_time'] ?? null;

        if (filled($validated['membermojo_password'] ?? null)) {
            $settings->membermojo_password = $validated['membermojo_password'];
        }

        if (blank($settings->membermojo_email)) {
            $settings->membermojo_password = null;
        }

        if ($before !== [$settings->membermojo_shortname, $settings->membermojo_email, $settings->membermojo_password]) {
            $settings->membermojo_cookies = null;
            $settings->membermojo_status = null;
        }

        $settings->save();
        Log::info('Membermojo download settings saved', ['configured' => filled($settings->membermojo_password)]);

        return redirect()->route('settings.membermojo')->with('status', __('The scheduled download was saved.'));
    }

    public function sync()
    {
        Artisan::call('membermojo:sync');

        return redirect()->route('settings.membermojo');
    }

    public function verify(Request $request, MembermojoClient $client)
    {
        $request->validate(
            ['verification_link' => ['required', 'url', 'starts_with:'.MembermojoClient::BASE_URL.'/']],
            ['verification_link.starts_with' => __('The link must be the one Membermojo emailed, starting :url.', ['url' => MembermojoClient::BASE_URL.'/'])]
        );

        try {
            $client->verify($request->input('verification_link'));
        } catch (MembermojoException $e) {
            throw ValidationException::withMessages(['verification_link' => $e->getMessage()]);
        }

        return $this->sync();
    }

    private function forgetPendingImport(Request $request): void
    {
        if ($path = $request->session()->pull('membermojo_import.path')) {
            Storage::disk('local')->delete($path);
        }

        $request->session()->forget('membermojo_import');
    }
}
