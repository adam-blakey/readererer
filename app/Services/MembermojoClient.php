<?php

namespace App\Services;

use App\Settings\GeneralSettings;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Downloads members.csv the way Membermojo supports for scripts: sign in, keep the cookies, fetch the file.
 */
class MembermojoClient
{
    public const BASE_URL = 'https://membermojo.co.uk';

    public function __construct(private GeneralSettings $settings) {}

    public static function configured(): bool
    {
        return filled(config('services.membermojo.shortname'))
            && filled(config('services.membermojo.email'))
            && filled(config('services.membermojo.password'));
    }

    public function download(): string
    {
        $jar = $this->jar();
        $site = self::BASE_URL.'/'.config('services.membermojo.shortname');

        try {
            $this->http($jar)->asForm()->post($site.'/signin_password', [
                'email' => config('services.membermojo.email'),
                'password' => config('services.membermojo.password'),
            ]);
            $response = $this->http($jar)->get($site.'/membership/download_members');
        } finally {
            $this->saveJar($jar);
        }

        if (! $response->successful() || ! str_contains(substr($response->body(), 0, 10000), MembermojoImporter::ID)) {
            throw new MembermojoException(__('Membermojo did not return the member list. Check the email address and password, or paste the verification link Membermojo emailed to that address.'));
        }

        return $response->body();
    }

    /** Opens the browser verification link Membermojo emails to a new browser, which sets the cookie later downloads need. */
    public function verify(string $link): void
    {
        $jar = $this->jar();

        try {
            $home = $this->http($jar)->get(self::BASE_URL)->body();

            if (! preg_match('/"csrf_token":"([^"]+)"/', $home, $match)) {
                throw new MembermojoException(__('Membermojo did not give a verification token. Run the download again and use the new link it emails.'));
            }

            $this->http($jar)->asForm()->post($link, ['csrf_token' => $match[1]]);
        } finally {
            $this->saveJar($jar);
        }
    }

    private function http(CookieJar $jar): PendingRequest
    {
        return Http::timeout(30)->withOptions(['cookies' => $jar]);
    }

    private function jar(): CookieJar
    {
        return new CookieJar(false, config('services.membermojo.cookies') ?? []);
    }

    private function saveJar(CookieJar $jar): void
    {
        $this->settings->membermojo_cookies = $jar->toArray();
        $this->settings->save();
        config(['services.membermojo.cookies' => $jar->toArray()]);
    }
}
