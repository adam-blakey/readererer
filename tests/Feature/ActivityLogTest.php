<?php

use App\Contracts\EmailSender;
use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Mail\VanDriverReminderMail;
use App\Models\Ensemble;
use App\Models\User;
use App\Services\EmailDispatcher;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

test('logins are logged by user id, and failed logins without the username', function () {
    Log::spy();
    $user = User::factory()->create();

    $this->post('/login', ['username' => $user->username, 'password' => 'wrong-password']);
    $this->post('/login', ['username' => $user->username, 'password' => 'password']);

    Log::shouldHaveReceived('warning')->with('Login failed', ['user_id' => $user->id])->once();
    Log::shouldHaveReceived('info')->with('Validation failed', ['fields' => ['username']])->once();
    Log::shouldHaveReceived('info')->with('User logged in', ['user_id' => $user->id, 'remember' => false])->once();
});

test('an invalid login link is logged', function () {
    Log::spy();

    $this->get(route('login-link.show', ['token' => 'not-a-token', 'email' => 'alice@example.com']))->assertRedirect();

    Log::shouldHaveReceived('warning')->with('Login link was invalid or had expired')->once();
});

test('poll replies to dates that have started are counted and logged as ignored', function () {
    Log::spy();
    $ensemble = Ensemble::factory()->create();
    $member = make_user();
    join_ensemble($member, $ensemble);
    $open = make_term_date();
    $started = make_term_date($open->term);
    $started->forceFill(['start_datetime' => now()->subHour()])->save();

    $this->actingAs($member)->post(route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $open->term->slug]), [
        "status-t{$open->id}m{$member->id}" => AttendanceStatus::Attending->value,
        "status-t{$started->id}m{$member->id}" => AttendanceStatus::Attending->value,
    ]);

    $context = ['ensemble_id' => $ensemble->id, 'term_id' => $open->term->id, 'replies' => 1];
    Log::shouldHaveReceived('info')->with('Poll replies saved', $context)->once();
    Log::shouldHaveReceived('warning')->with('Poll replies ignored because their dates are locked', $context)->once();
});

test('a failed email is logged by user id, not address', function () {
    Log::spy();
    app()->bind(EmailSender::class, fn () => new class implements EmailSender
    {
        public function send(User $recipient, Mailable $mailable): void
        {
            throw new RuntimeException('Connection refused');
        }
    });
    $driver = make_user();
    $termDate = make_term_date();

    app(EmailDispatcher::class)->send($termDate, collect([$driver]), fn () => new VanDriverReminderMail($termDate, $driver));

    Log::shouldHaveReceived('warning')->with('Failed to send notification email to a recipient.', Mockery::on(
        fn (array $context) => $context['user_id'] === $driver->id && ! in_array($driver->email, $context, true)
    ))->once();
});

test('changing a role is logged', function () {
    Log::spy();
    $member = make_user();

    $this->actingAs(make_user(UserRole::Admin))->put(route('users.update', $member), [
        'first_name' => $member->first_name,
        'last_name' => $member->last_name,
        'email' => $member->email,
        'username' => $member->username,
        'role' => UserRole::Moderator->value,
    ])->assertSessionHasNoErrors();

    Log::shouldHaveReceived('notice')->with('User role changed', ['user_id' => $member->id, 'from' => 'Member', 'to' => 'Moderator'])->once();
});
