<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermDate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'start_datetime',
        'end_datetime',
        'concert_ensemble_id',
        'setup_group_id',
        'van_driver_id',
    ];

    public function term(): BelongsTo
    {
        return $this->BelongsTo(Term::class);
    }

    public function setup_group(): BelongsTo
    {
        return $this->belongsTo(SetupGroup::class);
    }

    public function van_driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'van_driver_id', 'id');
    }

    public function getInferredVanDriverAttribute()
    {
        if ($this->van_driver != null) {
            return $this->van_driver;
        }

        if ($this->setup_group == null) {
            return null;
        }

        $previousCount = TermDate::where('setup_group_id', $this->setup_group_id)
            ->where('start_datetime', '<', $this->start_datetime)
            ->count();
        $vanDriversCount = $this->setup_group->van_drivers->count();

        if ($vanDriversCount == 0) {
            return null;
        }

        return $this->setup_group->van_drivers->get($previousCount % $vanDriversCount);
    }

    public function concert_ensemble(): BelongsTo
    {
        return $this->belongsTo(Ensemble::class, 'concert_ensemble_id');
    }

    public function email_logs(): HasMany
    {
        return $this->hasMany(EmailLog::class)->latest();
    }

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'concert_ensemble_id' => 'integer',
        ];
    }

    public function getNameAttribute(): string
    {
        if ($this->start_datetime->isSameDay($this->end_datetime)) {
            return $this->start_datetime->format('l, jS F Y').', '.$this->start_datetime->format('H:i').'–'.$this->end_datetime->format('H:i');
        }

        return $this->start_datetime->format('l, jS F Y, H:i').' – '.$this->end_datetime->format('l, jS F Y, H:i');
    }

    /**
     * The date portion, for rendering in its own table column. Multi-day
     * dates show the span; single-day dates just the one date.
     */
    public function getDateLabelAttribute(): string
    {
        if ($this->start_datetime->isSameDay($this->end_datetime)) {
            return $this->start_datetime->format('D, j M Y');
        }

        return $this->start_datetime->format('D, j M Y').' – '.$this->end_datetime->format('D, j M Y');
    }

    /**
     * The time portion, for rendering in its own table column.
     */
    public function getTimeLabelAttribute(): string
    {
        return $this->start_datetime->format('H:i').'–'.$this->end_datetime->format('H:i');
    }

    /**
     * Date and time together, on one line. Single-day dates read as
     * "Wed, 12 Aug 2026, 19:30–21:30"; multi-day dates spell out both ends.
     */
    public function getScheduleLabelAttribute(): string
    {
        if ($this->start_datetime->isSameDay($this->end_datetime)) {
            return $this->date_label.', '.$this->time_label;
        }

        return $this->start_datetime->format('D, j M Y, H:i').' – '.$this->end_datetime->format('D, j M Y, H:i');
    }

    /**
     * How far off the date is, in words — "Today", "In 3 days", "2 months ago" —
     * for showing alongside the absolute date.
     */
    public function getRelativeLabelAttribute(): string
    {
        // Rounded, so a day that is an hour short either side of a DST change
        // still counts as a whole day.
        $days = (int) round(now()->startOfDay()->diffInDays($this->start_datetime->copy()->startOfDay(), false));

        return match (true) {
            $days === 0 => 'Today',
            $days === 1 => 'Tomorrow',
            $days === -1 => 'Yesterday',
            $days > 1 && $days < 14 => 'In '.$days.' days',
            $days < -1 && $days > -14 => abs($days).' days ago',
            default => ucfirst($this->start_datetime->diffForHumans(['parts' => 1])),
        };
    }
}
