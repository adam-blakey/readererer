<?php

namespace App\Services;

use App\Enums\EmailStatus;
use App\Models\EmailLog;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailDispatcher
{
    /**
     * Send a mailable to each recipient and record it in an EmailLog. The
     * factory builds one mailable per recipient so addresses don't accumulate.
     *
     * @param  Collection<int, User>  $recipients
     * @param  callable(): Mailable  $mailableFactory
     */
    public function send(?TermDate $termDate, Collection $recipients, callable $mailableFactory): EmailLog
    {
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

            try {
                Mail::to($recipient)->send($mailableFactory());
            } catch (Throwable $exception) {
                $status = EmailStatus::Failed;
                $error = $exception->getMessage();
                $anyFailed = true;

                Log::warning('Failed to send notification email to a recipient.', [
                    'email_log_id' => $log->id,
                    'mailable' => $sample::class,
                    'recipient' => $recipient->email,
                    'error' => $error,
                ]);
            }

            $log->recipients()->create([
                'user_id' => $recipient->id,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'status' => $status,
                'error_message' => $error,
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
