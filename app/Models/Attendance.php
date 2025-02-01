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

    protected $edit_ip;
    protected $fillable = [
        'user_id',
        'term_date_id',
        'ensemble_id',
        'status',
        'edit_user_id',
        'edit_ip',
    ];

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

    public function getNameAttribute(): string
    {
        return $this->ensemble->name . ': ' . $this->term_date->term->name;
    }

    public function getStatusTextAttribute(): string
    {
        $assume_attending = config('app.readererer_assume_attending');

        $display_status = ($assume_attending and $this->status == AttendanceStatus::Unknown) ? AttendanceStatus::Attending : $this->status;

        switch ($display_status) {
            case AttendanceStatus::Attending:
                return 'Attending';

            case AttendanceStatus::NotAttending:
                return 'Not attending';

            case AttendanceStatus::Unknown:
                return 'Unknown';
        }
    }
}