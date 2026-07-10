<?php

namespace App\Models;

use App\Enums\EmailStatus;
use App\Mail\AttendanceListMail;
use App\Mail\RosterChangedMail;
use App\Mail\SetupReminderMail;
use App\Mail\VanDriverReminderMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Friendly names for the notification types, keyed by mailable class.
     */
    public const TYPE_LABELS = [
        AttendanceListMail::class => 'Attendance list',
        SetupReminderMail::class => 'Setup-group reminder',
        VanDriverReminderMail::class => 'Van-driver reminder',
        RosterChangedMail::class => 'Groups/drivers changed',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->mailable_class]
            ?? class_basename($this->mailable_class);
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
