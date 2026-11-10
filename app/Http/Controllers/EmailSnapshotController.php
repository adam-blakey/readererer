<?php

namespace App\Http\Controllers;

use App\Models\EmailRecipient;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class EmailSnapshotController extends Controller
{
    public function __invoke(EmailRecipient $emailRecipient): Response
    {
        $html = Storage::disk('local')->get(EmailRecipient::snapshotPath($emailRecipient->view_uuid));

        abort_if($html === null, 404);

        return response($html)->withHeaders([
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' https: data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none'",
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
