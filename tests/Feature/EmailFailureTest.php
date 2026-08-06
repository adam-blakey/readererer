<?php

use App\Enums\EmailStatus;
use App\Enums\UserRole;
use App\Models\EmailLog;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use Database\Seeders\EmailLogSeeder;
use Database\Seeders\EnsembleSeeder;
use Database\Seeders\InstrumentFamilySeeder;
use Database\Seeders\PieceSeeder;
use Database\Seeders\SetlistSeeder;
use Database\Seeders\SetupGroupSeeder;
use Database\Seeders\TermSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

function make_failure_term_date(array $attributes = []): TermDate
{
    $term = Term::factory()->create();

    return TermDate::forceCreate(array_merge([
        'term_id' => $term->id,
        'start_datetime' => '2026-06-01 19:00:00',
        'end_datetime' => '2026-06-01 21:00:00',
    ], $attributes));
}

/**
 * Swap the default mailer for one whose transport always throws, simulating
 * an unreachable/refusing mail server.
 */
function use_failing_mail_transport(): void
{
    Mail::extend('failing', fn () => new class extends AbstractTransport
    {
        protected function doSend(SentMessage $message): void
        {
            throw new TransportException('Connection could not be established with host "smtp.example.test:587"');
        }

        public function __toString(): string
        {
            return 'failing';
        }
    });

    config([
        'mail.mailers.failing' => ['transport' => 'failing'],
        'mail.default' => 'failing',
    ]);
}

test('a transport failure is recorded on the log and every recipient', function () {
    use_failing_mail_transport();

    $driver = make_user(UserRole::Member);
    $termDate = make_failure_term_date(['van_driver_id' => $driver->id]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('term-dates.send-van-driver-reminder', $termDate))
        ->assertRedirect()
        ->assertSessionHas('status');

    $log = EmailLog::where('term_date_id', $termDate->id)->first();
    expect($log->status)->toBe(EmailStatus::Failed);
    expect($log->recipients)->toHaveCount(1);

    $recipient = $log->recipients->first();
    expect($recipient->status)->toBe(EmailStatus::Failed);
    expect($recipient->error_message)->toContain('Connection could not be established');
});

test('a malformed recipient address fails without stopping the rest of the batch', function () {
    // The array mailer still builds the full message, so a non-RFC-compliant
    // address throws while everyone else sends normally.
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'color' => 'blue']);
    $badMember = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id, 'email' => 'not-a-valid-address']);
    $goodDriver = make_user(UserRole::Member, ['setup_group_id' => null]);
    $setupGroup->van_drivers()->attach($goodDriver->id);

    $termDate = make_failure_term_date(['setup_group_id' => $setupGroup->id]);

    $this->actingAs(make_user(UserRole::Moderator, ['setup_group_id' => null]))
        ->post(route('term-dates.send-setup-reminder', $termDate))
        ->assertRedirect();

    $log = EmailLog::where('term_date_id', $termDate->id)->first();

    // The batch as a whole is marked failed because one recipient failed...
    expect($log->status)->toBe(EmailStatus::Failed);
    expect($log->recipients)->toHaveCount(2);

    // ...but the failure was isolated: the bad address carries the error and
    // the valid recipient after it still went through.
    $failed = $log->recipients->firstWhere('email', 'not-a-valid-address');
    expect($failed->status)->toBe(EmailStatus::Failed);
    expect($failed->error_message)->not->toBeNull();

    $sent = $log->recipients->firstWhere('email', $goodDriver->email);
    expect($sent->status)->toBe(EmailStatus::Sent);
    expect($sent->error_message)->toBeNull();
});

test('failed sends are visible on the notifications overview', function () {
    use_failing_mail_transport();

    $driver = make_user(UserRole::Member);
    $termDate = make_failure_term_date(['van_driver_id' => $driver->id]);

    $moderator = make_user(UserRole::Moderator);

    $this->actingAs($moderator)
        ->post(route('term-dates.send-van-driver-reminder', $termDate));

    $this->actingAs($moderator)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Failed')
        ->assertSee($driver->name);
});

test('the email log seeder produces sent, partially failed and failed logs', function () {
    $this->seed(SetupGroupSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(InstrumentFamilySeeder::class);
    $this->seed(EnsembleSeeder::class);
    $this->seed(PieceSeeder::class);
    $this->seed(SetlistSeeder::class);
    $this->seed(TermSeeder::class);
    $this->seed(EmailLogSeeder::class);

    expect(EmailLog::where('status', EmailStatus::Sent)->count())->toBeGreaterThan(0);
    expect(EmailLog::where('status', EmailStatus::Failed)->count())->toBeGreaterThan(0);

    // The partial failure keeps its successful recipients alongside the failed one.
    $partial = EmailLog::where('status', EmailStatus::Failed)
        ->get()
        ->first(fn ($log) => $log->recipients->contains('status', EmailStatus::Sent));
    expect($partial)->not->toBeNull();
    expect($partial->recipients->firstWhere('status', EmailStatus::Failed)->error_message)->not->toBeNull();
});
