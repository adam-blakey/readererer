<?php

namespace App\Models;

use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmailRecipient extends Model
{
    protected $fillable = [
        'email_log_id',
        'user_id',
        'name',
        'email',
        'status',
        'error_message',
        'view_uuid',
    ];

    public const SNAPSHOT_DAYS = 90;

    protected $casts = [
        'status' => EmailStatus::class,
    ];

    public static function snapshotPath(string $uuid): string
    {
        return 'emails/'.$uuid.'.html';
    }

    public static function deleteSnapshots(Builder $query): void
    {
        $query->whereNotNull('view_uuid')->lazyById()->each(function (self $recipient) {
            Storage::disk('local')->delete(self::snapshotPath($recipient->view_uuid));
            $recipient->update(['view_uuid' => null]);
        });
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
