<?php

namespace Database\Seeders;

use App\Enums\EmailStatus;
use App\Mail\AttendanceListMail;
use App\Mail\RosterChangedMail;
use App\Mail\SetupReminderMail;
use App\Mail\VanDriverReminderMail;
use App\Models\EmailLog;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmailLogSeeder extends Seeder
{
    /**
     * Seed a realistic email history: fully successful sends, a partial
     * failure (one bad address in an otherwise fine batch), and a total
     * failure (mail server unreachable), so the notifications overview has
     * every state to show.
     */
    public function run(): void
    {
        $termDates = TermDate::orderBy('start_datetime')->take(4)->get();
        $users = User::whereNotNull('email')->take(6)->get();

        if ($termDates->isEmpty() || $users->isEmpty()) {
            return;
        }

        // A fully successful attendance list.
        $this->seedLog(
            termDate: $termDates[0],
            mailable: AttendanceListMail::class,
            subject: 'Attendance list — '.$termDates[0]->name,
            sentAt: now()->subDays(9),
            recipients: $users->take(2)->map(fn ($user) => [$user, EmailStatus::Sent, null]),
        );

        // A setup reminder where one recipient's address was rejected by the
        // receiving server, but the rest of the batch went through.
        $this->seedLog(
            termDate: $termDates[1] ?? $termDates[0],
            mailable: SetupReminderMail::class,
            subject: 'Setup reminder — '.($termDates[1] ?? $termDates[0])->name,
            sentAt: now()->subDays(6),
            recipients: $users->take(3)->values()->map(fn ($user, $index) => $index === 1
                ? [$user, EmailStatus::Failed, 'Expected response code "250" but got code "550", with message "550 5.1.1 The email account that you tried to reach does not exist".']
                : [$user, EmailStatus::Sent, null]),
        );

        // A van driver reminder where the mail server was unreachable, so
        // every recipient failed.
        $this->seedLog(
            termDate: $termDates[2] ?? $termDates[0],
            mailable: VanDriverReminderMail::class,
            subject: 'Van driver reminder — '.($termDates[2] ?? $termDates[0])->name,
            sentAt: now()->subDays(3),
            recipients: $users->take(1)->map(fn ($user) => [$user, EmailStatus::Failed, 'Connection could not be established with host "smtp.example.com:587": stream_socket_client(): Unable to connect (Connection refused)']),
        );

        // A recent, successful "groups/drivers changed" alert.
        $this->seedLog(
            termDate: $termDates[3] ?? $termDates[0],
            mailable: RosterChangedMail::class,
            subject: 'Setup group / van driver changed — '.($termDates[3] ?? $termDates[0])->name,
            sentAt: now()->subDay(),
            recipients: $users->take(4)->map(fn ($user) => [$user, EmailStatus::Sent, null]),
        );
    }

    /**
     * @param  Collection<int, array{0: User, 1: EmailStatus, 2: ?string}>  $recipients
     */
    private function seedLog(TermDate $termDate, string $mailable, string $subject, Carbon $sentAt, $recipients): void
    {
        $anyFailed = $recipients->contains(fn ($recipient) => $recipient[1] === EmailStatus::Failed);

        $log = EmailLog::create([
            'term_date_id' => $termDate->id,
            'mailable_class' => $mailable,
            'subject' => $subject,
            'status' => $anyFailed ? EmailStatus::Failed : EmailStatus::Sent,
        ]);

        foreach ($recipients as [$user, $status, $error]) {
            $log->recipients()->create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $status,
                'error_message' => $error,
            ]);
        }

        $log->timestamps = false;
        $log->created_at = $sentAt;
        $log->updated_at = $sentAt;
        $log->save();
    }
}
