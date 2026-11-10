<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Features\AttendanceListSections;
use App\Mail\AttendanceListMail;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;
use App\Services\EmailDispatcher;
use Illuminate\Support\Facades\Mail;
use Laravel\Pennant\Feature;

function attendance_list_with_replies(array $replies): TermDate
{
    $ensemble = Ensemble::factory()->create();
    $termDate = TermDate::factory()->create(['term_id' => Term::factory(), 'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHours(2)]);

    foreach ($replies as $name => [$status, $depName]) {
        [$first, $last] = explode(' ', $name);
        $member = make_user(UserRole::Member, ['first_name' => $first, 'last_name' => $last]);
        join_ensemble($member, $ensemble);

        if ($status) {
            Attendance::factory()->create(['user_id' => $member->id, 'term_date_id' => $termDate->id, 'ensemble_id' => $ensemble->id, 'status' => $status, 'dep_name' => $depName]);
        }
    }

    return $termDate;
}

function section_of(string $html, string $heading): string
{
    return preg_match('/>'.preg_quote($heading, '/').' \(\d+\)<\/h2>(.*?)<\/table>/s', $html, $match) ? $match[1] : '';
}

beforeEach(function () {
    config([
        'app.readererer_feature_flags' => [AttendanceListSections::class => ['enabled' => true]],
        'app.readererer_assume_attending' => false,
        'app.readererer_depping' => true,
    ]);
    Feature::flushCache();
});

test('the attendance list splits members into sections with totals, deps and the time it was sent', function () {
    $this->travelTo(now()->setDateTime(2026, 11, 10, 18, 5));

    $termDate = attendance_list_with_replies([
        'Ann Attending' => [AttendanceStatus::Attending, null],
        'Dan Depper' => [AttendanceStatus::Depping, 'Sub Stitute'],
        'Nora Nope' => [AttendanceStatus::NotAttending, null],
        'Sam Silent' => [null, null],
    ]);

    $html = (string) (new AttendanceListMail($termDate))->render();

    expect($html)->toContain('Sent Tuesday, 10th November 2026, 18:05')
        ->toContain('1 member is sending a dep.')
        ->toContain('width="50%"')
        ->toContain('width="25%"')
        ->toContain('(50%)')
        ->and(section_of($html, 'Attending'))->toContain('Ann Attending')->toMatch('/<s[^>]*>Dan Depper<\/s> <span[^>]*>depped by Sub Stitute<\/span>/')->not->toContain('Nora Nope')
        ->and(section_of($html, 'Not attending'))->toContain('Nora Nope')->toContain('alt="Not attending"')->not->toContain('Sam Silent')
        ->and(section_of($html, 'Not yet responded'))->toContain('Sam Silent')->toContain('alt="Not yet responded"');
});

test('the attendance list has no unknown section or total when attending is assumed', function () {
    config(['app.readererer_assume_attending' => true]);

    $html = (string) (new AttendanceListMail(attendance_list_with_replies(['Sam Silent' => [null, null]])))->render();

    expect($html)->not->toContain('Not yet responded')
        ->and(section_of($html, 'Attending'))->toContain('Sam Silent');
});

test('the attendance list shows an earlier dep as attending while depping is off', function () {
    config(['app.readererer_depping' => false]);

    $html = (string) (new AttendanceListMail(attendance_list_with_replies(['Dan Depper' => [AttendanceStatus::Depping, 'Sub Stitute']])))->render();

    expect($html)->not->toContain('sending a dep')
        ->not->toContain('Sub Stitute')
        ->and(section_of($html, 'Attending'))->toContain('Dan Depper');
});

test('the attendance list layout follows the flag for each recipient', function () {
    Mail::fake();
    config(['app.readererer_feature_flags' => [AttendanceListSections::class => ['enabled' => true, 'roles' => [UserRole::Admin->value]]]]);
    Feature::flushCache();
    $termDate = attendance_list_with_replies(['Ann Attending' => [AttendanceStatus::Attending, null]]);
    $admin = make_user(UserRole::Admin);
    $moderator = make_user(UserRole::Moderator);

    app(EmailDispatcher::class)->send($termDate, collect([$admin, $moderator]), fn ($recipient = null) => new AttendanceListMail($termDate, $recipient));

    Mail::assertSent(AttendanceListMail::class, fn ($mail) => $mail->hasTo($admin->email) && $mail->content()->markdown === 'emails.attendance-list-sections');
    Mail::assertSent(AttendanceListMail::class, fn ($mail) => $mail->hasTo($moderator->email) && $mail->content()->markdown === 'emails.attendance-list');
});
