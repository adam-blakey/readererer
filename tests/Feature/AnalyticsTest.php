<?php

use App\Console\Commands\SendAnalytics;
use App\Contracts\EmailSender;
use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function record_event(array $attributes = [], string $name = 'request'): void
{
    DB::table('analytics_events')->insert([
        'name' => $name,
        'attributes' => json_encode(array_merge(['outcome' => 'success', 'http.route' => 'terms.show', 'http.response.status_code' => 200], $attributes)),
        'created_at' => now(),
    ]);
}

/** @return array<int, array<string, mixed>> the attributes of each recorded event with this name */
function recorded_events(string $name): array
{
    return DB::table('analytics_events')->where('name', $name)->pluck('attributes')->map(fn ($attributes) => json_decode($attributes, true))->all();
}

function use_email_sender(bool $fails): void
{
    app()->bind(EmailSender::class, fn () => new class($fails) implements EmailSender
    {
        public function __construct(private bool $fails) {}

        public function send(User $recipient, Mailable $mailable): void
        {
            if ($this->fails) {
                throw new RuntimeException('Connection refused');
            }
        }
    });
}

test('nothing is recorded until analytics are switched on', function () {
    $this->actingAs(make_user(UserRole::Admin))->get(route('dashboard'))->assertOk();

    expect(DB::table('analytics_events')->count())->toBe(0);
});

test('a request is recorded by route name without the URL or the user', function () {
    config(['app.readererer_analytics' => true]);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('users.show', $admin).'?secret=1')->assertOk();

    $event = DB::table('analytics_events')->sole();
    expect($event->name)->toBe('request')
        ->and(json_decode($event->attributes, true))->toMatchArray([
            'outcome' => 'success',
            'http.request.method' => 'GET',
            'http.route' => 'users.show',
            'http.response.status_code' => 200,
        ])
        ->and(json_encode($event))->not->toContain($admin->email)->not->toContain('secret');
});

test('the batch is sent as OTLP logs and removed once accepted', function () {
    config(['app.readererer_analytics' => true]);
    Http::fake([SendAnalytics::URL => Http::response()]);
    record_event();
    record_event(['outcome' => 'failure', 'http.response.status_code' => 500]);

    $this->artisan('analytics:send')->assertSuccessful();

    Http::assertSent(function (Request $request) {
        $records = $request['resourceLogs'][0]['scopeLogs'][0]['logRecords'];

        return $request->url() === SendAnalytics::URL
            && count($records) === 2
            && $records[0]['severityText'] === 'INFO'
            && $records[1]['severityText'] === 'ERROR'
            && $records[1]['body'] === ['stringValue' => 'request']
            && in_array(['key' => 'http.response.status_code', 'value' => ['intValue' => 500]], $records[1]['attributes'], true);
    });
    expect(DB::table('analytics_events')->count())->toBe(0);
});

test('failed sends are kept for retrying and dropped after the last attempt', function () {
    config(['app.readererer_analytics' => true]);
    Http::fake([SendAnalytics::URL => Http::failedConnection()]);
    record_event();

    foreach (range(1, SendAnalytics::MAX_ATTEMPTS - 1) as $attempt) {
        $this->artisan('analytics:send')->assertSuccessful();
    }
    expect(DB::table('analytics_events')->value('attempts'))->toBe(SendAnalytics::MAX_ATTEMPTS - 1);

    Http::fake([SendAnalytics::URL => Http::response(status: 503)]);
    $this->artisan('analytics:send')->assertSuccessful();

    expect(DB::table('analytics_events')->count())->toBe(0);
});

test('switching analytics off discards what was recorded without sending it', function () {
    Http::fake();
    record_event();

    $this->artisan('analytics:send')->assertSuccessful();

    Http::assertNothingSent();
    expect(DB::table('analytics_events')->count())->toBe(0);
});

test('logins are recorded as succeeding or failing without the username', function () {
    config(['app.readererer_analytics' => true]);
    $user = User::factory()->create();

    $this->post('/login', ['username' => $user->username, 'password' => 'wrong-password']);
    $this->post('/login', ['username' => $user->username, 'password' => 'password']);

    expect(recorded_events('login'))->toBe([
        ['outcome' => 'failure', 'http.route' => 'login'],
        ['outcome' => 'success', 'http.route' => 'login'],
    ]);
});

test('emails are recorded with how many recipients failed', function (bool $fails) {
    config(['app.readererer_analytics' => true]);
    use_email_sender($fails);
    $termDate = TermDate::forceCreate([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => '2026-06-01 19:00:00',
        'end_datetime' => '2026-06-01 21:00:00',
        'van_driver_id' => make_user(UserRole::Member)->id,
    ]);

    $this->actingAs(make_user(UserRole::Admin))->post(route('term-dates.send-van-driver-reminder', $termDate));

    expect(recorded_events('email'))->toBe([[
        'outcome' => $fails ? 'failure' : 'success',
        'email.type' => 'VanDriverReminderMail',
        'email.recipients' => 1,
        'email.failures' => $fails ? 1 : 0,
    ]]);
})->with(['sent' => false, 'failed' => true]);

test('validation failures are recorded with the field names but not the values', function () {
    config(['app.readererer_analytics' => true]);

    $this->actingAs(make_user(UserRole::Admin))->put(route('settings.update'), ['name' => '', 'image_max_kilobytes' => 'secret-value']);

    $event = recorded_events('validation')[0];
    expect($event['outcome'])->toBe('failure')
        ->and($event['http.route'])->toBe('settings.update')
        ->and(explode(',', $event['fields']))->toContain('name', 'image_max_kilobytes')
        ->and(json_encode($event))->not->toContain('secret-value');
});

test('reported exceptions are recorded by their type only', function () {
    config(['app.readererer_analytics' => true]);

    report(new RuntimeException('a secret detail'));

    expect(recorded_events('exception'))->toBe([['outcome' => 'failure', 'exception.type' => RuntimeException::class]]);
});

test('poll replies are counted', function () {
    config(['app.readererer_analytics' => true]);
    $ensemble = Ensemble::factory()->create();
    $member = make_user();
    join_ensemble($member, $ensemble);
    $termDate = make_term_date();

    $this->actingAs(make_user(UserRole::Admin))->post(
        route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $termDate->term->slug]),
        ["status-t{$termDate->id}m{$member->id}" => AttendanceStatus::Attending->value, 'status-t0m0' => AttendanceStatus::Attending->value],
    );

    expect(recorded_events('attendance.poll'))->toBe([['outcome' => 'success', 'replies' => 1]]);
});
