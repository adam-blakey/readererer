<?php

namespace App\Console\Commands;

use App\Services\MembermojoClient;
use App\Services\MembermojoException;
use App\Services\MembermojoImporter;
use App\Settings\GeneralSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncMembermojo extends Command
{
    protected $signature = 'membermojo:sync';

    protected $description = 'Download the members list from Membermojo and import it';

    public function handle(MembermojoClient $client, GeneralSettings $settings): int
    {
        if (! MembermojoClient::configured()) {
            return self::SUCCESS;
        }

        try {
            $rows = MembermojoImporter::parse($client->download());
            $report = (new MembermojoImporter($rows, (bool) config('services.membermojo.overwrite')))->import();
            $status = ['ok' => true, 'counts' => $report['counts'], 'warnings' => $report['warnings'], 'leavers' => $report['leavers']];
            Log::info('Membermojo sync finished', ['counts' => $report['counts'], 'warnings' => count($report['warnings'])]);
        } catch (Throwable $e) {
            $status = ['ok' => false, 'error' => match (true) {
                $e instanceof MembermojoException, $e instanceof ValidationException => $e->getMessage(),
                default => __('Membermojo could not be reached.'),
            }];
            Log::error('Membermojo sync failed', ['exception' => $e::class]);
        }

        $settings->membermojo_status = $status + ['at' => now()->toIso8601String()];
        $settings->save();

        return $status['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
