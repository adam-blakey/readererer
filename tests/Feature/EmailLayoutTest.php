<?php

use App\Enums\EmailLinkType;
use App\Mail\AttendanceChangedMail;
use App\Mail\AttendanceListMail;
use App\Mail\RosterChangedMail;
use App\Mail\SetupReminderMail;
use App\Mail\VanDriverReminderMail;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Notifications\EmailLink;

function every_email(): array
{
    $termDate = make_term_date();
    $user = make_user();

    return [
        (string) (new AttendanceListMail($termDate))->render(),
        (string) (new AttendanceChangedMail($termDate, Ensemble::factory()->create(), [['name' => 'Ann', 'from' => 'Unknown', 'to' => 'Attending']]))->render(),
        (string) (new RosterChangedMail($termDate, ['The van driver changed.']))->render(),
        (string) (new SetupReminderMail($termDate, SetupGroup::factory()->create()))->render(),
        (string) (new VanDriverReminderMail($termDate, $user))->render(),
        ...array_map(fn (EmailLinkType $type) => (string) (new EmailLink($type, 'token'))->toMail($user)->render(), EmailLinkType::cases()),
    ];
}

test('no email links to the Tabler demo or carries its placeholder text', function () {
    foreach (every_email() as $html) {
        expect($html)->not->toContain('tabler.io')
            ->not->toContain('utm_source')
            ->not->toContain('href="#"')
            ->not->toContain('preheader text')
            ->toContain('href="mailto:'.config('mail.from.address').'"')
            ->toContain('href="'.config('app.url').'"');
    }
});

test('emails show no social row until a link is set', function () {
    $html = (string) (new RosterChangedMail(make_term_date(), []))->render();

    expect($html)->not->toContain('icons-gray-social');
});

test('emails show an icon for each social link that is set, and only those', function () {
    config(['app.readererer_social_links' => ['facebook' => 'https://facebook.com/riverside', 'youtube' => 'https://youtube.com/@riverside']]);

    $html = (string) (new RosterChangedMail(make_term_date(), []))->render();

    expect($html)->toContain('href="https://facebook.com/riverside"')
        ->toContain('alt="Facebook"')
        ->toContain('href="https://youtube.com/@riverside"')
        ->toContain('alt="YouTube"')
        ->not->toContain('alt="Instagram"')
        ->not->toContain('alt="X"');
});
