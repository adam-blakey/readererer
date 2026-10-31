<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

class DeployController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = config('app.deploy_token');
        abort_unless(filled($token) && hash_equals($token, (string) $request->bearerToken()), 404);

        set_time_limit(0);
        $output = '';

        try {
            foreach (['migrate' => ['--force' => true], 'optimize' => [], 'event:cache' => [], 'queue:restart' => []] as $command => $arguments) {
                Artisan::call($command, $arguments);
                $output .= Artisan::output();
            }
        } finally {
            Artisan::call('up');
        }

        return response($output.Artisan::output(), 200, ['Content-Type' => 'text/plain']);
    }
}
