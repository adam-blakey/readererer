<?php

namespace App\Models;

use App\Enums\EmailStatus;
use App\Mail\AttendanceChangedMail;
use App\Mail\AttendanceListMail;
use App\Mail\RosterChangedMail;
use App\Mail\SetupReminderMail;
use App\Mail\VanDriverReminderMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Mail\Mailable;

class EmailLog extends Model
{
    protected $fillable = [
        'term_date_id',
        'mailable_class',
        'mailable_args',
        'subject',
        'html_path',
        'status',
        'error_message',
    ];

    protected $casts = [
        'mailable_args' => 'array',
        'status' => EmailStatus::class,
    ];

    public const TYPE_LABELS = [
        AttendanceListMail::class => 'Attendance list',
        AttendanceChangedMail::class => 'Attendance changed',
        SetupReminderMail::class => 'Setup-group reminder',
        VanDriverReminderMail::class => 'Van-driver reminder',
        RosterChangedMail::class => 'Setup groups/driver changed',
    ];

    public function getTypeLabelAttribute(): string
    {
        return isset(self::TYPE_LABELS[$this->mailable_class])
            ? __(self::TYPE_LABELS[$this->mailable_class])
            : class_basename($this->mailable_class);
    }

    /** Whether the email reached anyone: the one rule for "already sent", so a partly failed send counts and a pending one does not. */
    public function wasDelivered(): bool
    {
        return $this->recipients->contains('status', EmailStatus::Sent);
    }

    /** Recipients whose copy failed, who a resend should reach. */
    public function failedRecipients(): HasMany
    {
        return $this->recipients()->where('status', EmailStatus::Failed);
    }

    /** A fresh mailable of this type, or null for types that cannot be rebuilt from the date alone. */
    public function resendableMailable(?User $recipient = null): ?Mailable
    {
        $termDate = $this->termDate;

        return match (true) {
            $termDate === null => null,
            $this->mailable_class === AttendanceListMail::class => new AttendanceListMail($termDate, $recipient),
            $this->mailable_class === SetupReminderMail::class && $termDate->setup_group !== null => new SetupReminderMail($termDate, $termDate->setup_group),
            $this->mailable_class === VanDriverReminderMail::class && $termDate->inferred_van_driver !== null => new VanDriverReminderMail($termDate, $termDate->inferred_van_driver),
            default => null,
        };
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailRecipient::class);
    }

    public function termDate(): BelongsTo
    {
        return $this->belongsTo(TermDate::class);
    }
}
