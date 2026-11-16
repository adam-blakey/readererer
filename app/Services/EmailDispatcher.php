<?php

namespace App\Services;

use App\Contracts\EmailSender;
use App\Enums\EmailStatus;
use App\Features\ViewEmailOnline;
use App\Models\EmailLog;
use App\Models\EmailRecipient;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Throwable;

class EmailDispatcher
{
    public function __construct(private EmailSender $sender) {}

    /**
     * Send a mailable to each recipient and record it in an EmailLog. The
     * factory builds one mailable per recipient so addresses don't accumulate.
     * Nothing is sent, and null returned, for a date whose ensembles get no emails.
     *
     * @param  Collection<int, User>  $recipients
     * @param  callable(?User): Mailable  $mailableFactory  given the recipient, or null for the sample that names the log
     */
    public function send(?TermDate $termDate, Collection $recipients, callable $mailableFactory): ?EmailLog
    {
        if ($termDate?->sendsNoEmails()) {
            Log::info('Notification email not sent because its date is only for hidden ensembles.', ['term_date_id' => $termDate->id]);

            return null;
        }

        $sample = $mailableFactory();

        $log = EmailLog::create([
            'term_date_id' => $termDate?->id,
            'mailable_class' => $sample::class,
            'subject' => $sample->envelope()->subject,
            'status' => EmailStatus::Pending,
        ]);

        Log::info('Sending notification email.', [
            'email_log_id' => $log->id,
            'mailable' => $sample::class,
            'subject' => $log->subject,
            'term_date_id' => $termDate?->id,
            'recipient_count' => $recipients->count(),
        ]);

        $anyFailed = false;

        foreach ($recipients as $recipient) {
            $status = EmailStatus::Sent;
            $error = null;
            $viewUuid = Feature::for($recipient)->active(ViewEmailOnline::class) ? (string) Str::uuid() : null;

            try {
                $snapshot = $viewUuid ? $mailableFactory($recipient)->render() : null;

                View::share('viewOnlineUrl', $viewUuid ? route('emails.show', $viewUuid) : null);
                $this->sender->send($recipient, $mailableFactory($recipient));

                if ($viewUuid && ! Storage::disk('local')->put(EmailRecipient::snapshotPath($viewUuid), $snapshot)) {
                    $viewUuid = null;
                }
            } catch (Throwable $exception) {
                $viewUuid = null;
                $status = EmailStatus::Failed;
                $error = $exception->getMessage();
                $anyFailed = true;

                Log::warning('Failed to send notification email to a recipient.', [
                    'email_log_id' => $log->id,
                    'mailable' => $sample::class,
                    'user_id' => $recipient->id,
                    'error' => $error,
                ]);
            } finally {
                View::share('viewOnlineUrl', null);
            }

            $log->recipients()->create([
                'user_id' => $recipient->id,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'status' => $status,
                'error_message' => $error,
                'view_uuid' => $viewUuid,
            ]);
        }

        $log->update(['status' => $anyFailed ? EmailStatus::Failed : EmailStatus::Sent]);

        Log::info('Finished sending notification email.', [
            'email_log_id' => $log->id,
            'status' => $log->status->name,
        ]);

        return $log;
    }
}
