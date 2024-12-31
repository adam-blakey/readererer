<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\AttendanceStatus;

class Attendance extends Model
{
    use HasFactory;

    protected $edit_datetime;
    protected $edit_ip;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function edit_user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function term_date(): BelongsTo
    {
        return $this->belongsTo(TermDate::class);
    }

    public function ensemble(): BelongsTo
    {
        return $this->belongsTo(Ensemble::class);
    }

    public function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    public function getStatusTextAttribute(): string
    {
        switch ($this->status) {
            case AttendanceStatus::NotAttending:
                return 'Not Attending';
            case AttendanceStatus::Attending:
                return 'Attending';
            default:
                return 'Unknown';
        }
    }
}