<?php

use App\Contracts\EmailSender;
use App\Enums\EmailLinkType;
use App\Enums\EmailStatus;
use App\Enums\UserRole;
use App\Features\ResendFailedNotifications;
use App\Features\ViewEmailOnline;
use App\Mail\RosterChangedMail;
use App\Mail\VanDriverReminderMail;
use App\Models\EmailLog;
use App\Models\EmailRecipient;
use App\Models\User;
use App\Notifications\EmailLink;
use App\Services\EmailDispatcher;
use App\Services\EmailSenders\LogEmailSender;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Storage::fake('local');
});

function enable_view_online(array $flags = []): void
{
    config(['app.readererer_feature_flags' => [ViewEmailOnline::class => ['enabled' => true], ...$flags]]);
    Feature::flushCache();
}

/** Sends through an EmailSender that keeps the HTML it was given, so tests can read the email as sent. */
function capture_sent_html(): ArrayObject
{
    $sent = new ArrayObject;
    app()->bind(EmailSender::class, fn () => new class($sent) implements EmailSender
    {
        public function __construct(private ArrayObject $sent) {}

        public function send(User $recipient, Mailable $mailable): void
        {
            $this->sent[$recipient->id] = (string) $mailable->render();
        }
    });

    return $sent;
}

function send_roster_email(User ...$recipients): EmailLog
{
    $termDate = make_term_date();

    return app(EmailDispatcher::class)->send($termDate, collect($recipients), fn () => new RosterChangedMail($termDate, ['The van driver changed.']));
}

test('with the flag on, each recipient gets a view online link to a stored copy of their email', function () {
    enable_view_online();
    $sent = capture_sent_html();
    $first = make_user();
    $second = make_user();

    send_roster_email($first, $second);

    $recipients = EmailRecipient::all();
    expect($recipients->pluck('view_uuid')->filter()->unique())->toHaveCount(2);

    foreach ($recipients as $recipient) {
        $url = route('emails.show', $recipient->view_uuid);
        Storage::disk('local')->assertExists('emails/'.$recipient->view_uuid.'.html');

        expect($sent[$recipient->user_id])->toContain('href="'.$url.'"')->toContain(__('View online'));

        $this->get($url)
            ->assertOk()
            ->assertSee('The van driver changed.')
            ->assertDontSee(__('View online'))
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
});

test('the stored copy is the sent email without its own view online link', function () {
    enable_view_online();
    $sent = capture_sent_html();
    $member = make_user();

    send_roster_email($member);

    $uuid = EmailRecipient::sole()->view_uuid;
    $stored = Storage::disk('local')->get('emails/'.$uuid.'.html');
    $link = preg_quote(route('emails.show', $uuid), '/');

    $withoutLink = preg_replace('/<td align="right"[^>]*>\s*<a [^>]*'.$link.'.*?<\/td>/s', '', $sent[$member->id]);

    expect(preg_replace('/\s+/', ' ', $withoutLink))->toBe(preg_replace('/\s+/', ' ', $stored));
});

test('the view online page has a policy that runs no scripts and allows the email images', function () {
    enable_view_online();
    send_roster_email(make_user());

    $policy = $this->get(route('emails.show', EmailRecipient::sole()->view_uuid))->headers->get('Content-Security-Policy');

    expect($policy)->toStartWith("default-src 'none';")
        ->toContain("img-src 'self' https: data:")
        ->toContain("style-src 'unsafe-inline'")
        ->not->toContain('script-src');
});

test('with the flag off, no copy is kept and the email has no view online link', function () {
    $sent = capture_sent_html();
    $member = make_user();

    send_roster_email($member);

    expect(EmailRecipient::sole()->view_uuid)->toBeNull()
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and($sent[$member->id])->not->toContain(__('View online'));
});

test('the flag follows the recipient, so it can be rolled out to some roles', function () {
    enable_view_online([ViewEmailOnline::class => ['enabled' => true, 'roles' => [UserRole::Admin->value]]]);
    capture_sent_html();
    $admin = make_user(UserRole::Admin);
    $member = make_user();

    send_roster_email($admin, $member);

    expect(EmailRecipient::where('user_id', $admin->id)->sole()->view_uuid)->not->toBeNull()
        ->and(EmailRecipient::where('user_id', $member->id)->sole()->view_uuid)->toBeNull();
});

test('a recipient the email failed to reach gets no stored copy', function () {
    enable_view_online();
    app()->bind(EmailSender::class, fn () => new class implements EmailSender
    {
        public function send(User $recipient, Mailable $mailable): void
        {
            throw new RuntimeException('Mail server unreachable');
        }
    });

    send_roster_email(make_user());

    expect(EmailRecipient::sole())->status->toBe(EmailStatus::Failed)->view_uuid->toBeNull()
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('the log sender writes the view online link into the logged email', function () {
    enable_view_online();
    app()->bind(EmailSender::class, LogEmailSender::class);
    Log::spy();
    Log::shouldReceive('channel')->with('emails')->andReturnSelf();

    send_roster_email(make_user());

    $url = route('emails.show', EmailRecipient::sole()->view_uuid);
    Log::shouldHaveReceived('info')->withArgs(fn (string $message) => str_starts_with($message, 'To: ') && str_contains($message, 'href="'.$url.'"'))->once();
});

test('a resent email gets a fresh stored copy', function () {
    Mail::fake();
    config(['app.readererer_van_drivers' => true]);
    enable_view_online([ResendFailedNotifications::class => ['enabled' => true]]);
    $driver = make_user();
    $termDate = make_term_date();
    $termDate->forceFill(['van_driver_id' => $driver->id])->save();
    $failed = EmailLog::create(['term_date_id' => $termDate->id, 'mailable_class' => VanDriverReminderMail::class, 'subject' => 'Van', 'status' => EmailStatus::Failed]);
    $failed->recipients()->create(['user_id' => $driver->id, 'name' => $driver->name, 'email' => $driver->email, 'status' => EmailStatus::Failed]);

    $this->actingAs(make_user(UserRole::Admin))->post(route('notifications.resend', $failed))->assertRedirect();

    $resent = EmailLog::latest('id')->first()->recipients()->sole();
    Mail::assertSent(VanDriverReminderMail::class, 1);
    expect($resent->view_uuid)->not->toBeNull();
    $this->get(route('emails.show', $resent->view_uuid))->assertOk()->assertSee(__('Van driver reminder'));
});

test('an unknown or malformed id is not found', function (string $id) {
    $this->get('/emails/'.$id)->assertNotFound();
})->with([
    'unknown' => fn () => (string) Str::uuid(),
    'not a uuid' => 'not-a-uuid',
]);

test('a copy whose file has gone is not found', function () {
    enable_view_online();
    send_roster_email(make_user());
    $uuid = EmailRecipient::sole()->view_uuid;
    Storage::disk('local')->delete('emails/'.$uuid.'.html');

    $this->get(route('emails.show', $uuid))->assertNotFound();
});

test('the view online page is public and rate limited', function () {
    enable_view_online();
    send_roster_email(make_user());
    $url = route('emails.show', EmailRecipient::sole()->view_uuid);

    foreach (range(1, 60) as $ignored) {
        $this->get($url)->assertOk();
    }

    $this->get($url)->assertTooManyRequests();
});

test('copies older than the retention period are deleted by the schedule', function () {
    enable_view_online();
    send_roster_email(make_user());
    send_roster_email(make_user());
    [$old, $recent] = EmailRecipient::orderBy('id')->get();
    $old->forceFill(['created_at' => now()->subDays(EmailRecipient::SNAPSHOT_DAYS + 1)])->save();

    $this->artisan('schedule:list')->expectsOutputToContain('delete-email-snapshots');
    collect(app(Schedule::class)->events())->firstWhere('description', 'delete-email-snapshots')->run(app());

    expect($old->fresh()->view_uuid)->toBeNull()
        ->and($recent->fresh()->view_uuid)->not->toBeNull();
    Storage::disk('local')->assertMissing('emails/'.$old->view_uuid.'.html');
    Storage::disk('local')->assertExists('emails/'.$recent->view_uuid.'.html');
});

test('erasing a user deletes their copies and every copy that names them', function () {
    enable_view_online();
    capture_sent_html();
    $user = make_user(UserRole::Member, ['first_name' => 'Ophelia', 'last_name' => "O'Hara"]);
    $other = make_user();
    $unrelated = make_user();
    $termDate = make_term_date();
    $dispatcher = app(EmailDispatcher::class);
    $dispatcher->send($termDate, collect([$user]), fn () => new RosterChangedMail($termDate, ['Nothing personal.']));
    $dispatcher->send($termDate, collect([$other]), fn () => new RosterChangedMail($termDate, ["Ophelia O'Hara now drives the van."]));
    $dispatcher->send($termDate, collect([$unrelated]), fn () => new RosterChangedMail($termDate, ['Nothing personal.']));
    $uuids = EmailRecipient::pluck('view_uuid', 'user_id');

    $this->actingAs(make_user(UserRole::Admin))->delete(route('users.erase', $user), ['confirm_name' => $user->name])->assertRedirect();

    $this->get(route('emails.show', $uuids[$user->id]))->assertNotFound();
    $this->get(route('emails.show', $uuids[$other->id]))->assertNotFound();
    $this->get(route('emails.show', $uuids[$unrelated->id]))->assertOk();
});

test('password reset and login link emails have no view online link and are not kept', function (EmailLinkType $type) {
    enable_view_online();
    $user = make_user();

    $html = (string) (new EmailLink($type, 'token'))->toMail($user)->render();

    expect($html)->not->toContain(__('View online'))
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(EmailRecipient::count())->toBe(0);
})->with([EmailLinkType::ResetPassword, EmailLinkType::LoginLink]);

test('sending a login link stores nothing', function () {
    enable_view_online();
    Notification::fake();

    EmailLinkType::LoginLink->send(make_user()->email);

    expect(Storage::disk('local')->allFiles())->toBe([])
        ->and(EmailRecipient::count())->toBe(0);
});
