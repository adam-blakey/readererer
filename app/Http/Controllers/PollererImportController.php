<?php

namespace App\Http\Controllers;

use App\Services\PollererImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PollererImportController extends Controller
{
    public function create(Request $request)
    {
        return view('settings.import', [
            'page_name' => __('Import from Pollererer'),
            'preview' => $request->session()->get('pollererer_import.report'),
            'summary' => $request->session()->get('pollererer_import_summary'),
            'site_is_empty' => PollererImporter::siteIsEmpty(),
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate(['export' => ['required', 'file']]);
        $this->ensureSiteIsEmpty();

        $export = PollererImporter::parse($request->file('export')->get());

        $this->forgetPendingImport($request);
        $path = $request->file('export')->store('imports', 'local');
        $report = (new PollererImporter($export, $request->user()))->preview();
        $request->session()->put('pollererer_import', ['path' => $path, 'report' => $report]);

        return redirect()->route('settings.import');
    }

    public function store(Request $request)
    {
        $pending = $request->session()->get('pollererer_import');

        if (! $pending || ! Storage::disk('local')->exists($pending['path'])) {
            throw ValidationException::withMessages(['export' => __('Upload the export again.')]);
        }

        $request->validate(['acknowledge_timezone' => $pending['report']['timezone_warning'] ? ['accepted'] : []]);
        $this->ensureSiteIsEmpty();

        try {
            $export = PollererImporter::parse(Storage::disk('local')->get($pending['path']));
            $summary = (new PollererImporter($export, $request->user()))->import();
        } finally {
            $this->forgetPendingImport($request);
        }

        return redirect()->route('settings.import')->with('pollererer_import_summary', $summary);
    }

    private function ensureSiteIsEmpty(): void
    {
        if (! PollererImporter::siteIsEmpty()) {
            throw ValidationException::withMessages(['export' => __('Pollererer data can only be imported into a site with no ensembles, terms, dates or attendance.')]);
        }
    }

    private function forgetPendingImport(Request $request): void
    {
        if ($path = $request->session()->pull('pollererer_import.path')) {
            Storage::disk('local')->delete($path);
        }

        $request->session()->forget('pollererer_import');
    }
}
