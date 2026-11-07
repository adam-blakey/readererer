<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Observers\TermDateObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

#[ObservedBy(TermDateObserver::class)]
class TermDate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'start_datetime',
        'end_datetime',
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

        if ($this->setup_group == null || ! config('app.readererer_setup_groups')) {
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

    public function concert_ensembles(): BelongsToMany
    {
        return $this->belongsToMany(Ensemble::class, 'concert_ensemble')->orderBy('name');
    }

    public function getIsConcertAttribute(): bool
    {
        return $this->concert_ensembles->isNotEmpty();
    }

    public function getConcertEnsembleNamesAttribute(): string
    {
        return $this->concert_ensembles->pluck('name')->join(', ');
    }

    public function email_logs(): HasMany
    {
        return $this->hasMany(EmailLog::class)->latest();
    }

    public function register_entries(): HasMany
    {
        return $this->hasMany(RegisterEntry::class);
    }

    /** Rehearsals apply to every ensemble; a concert only to the ensembles playing it. */
    public function appliesToEnsemble(Ensemble $ensemble): bool
    {
        return ! $this->is_concert || $this->concert_ensembles->contains($ensemble);
    }

    /** When the automatic reminders go out, or null when they are turned off. */
    public function remindersDueAt(): ?Carbon
    {
        $hours = (int) config('app.readererer_reminder_hours');

        return $hours > 0 ? $this->start_datetime->copy()->subHours($hours) : null;
    }

    public function isPastReminderTime(): bool
    {
        return $this->remindersDueAt()?->isPast() ?? false;
    }

    /** Polls lock for everyone below moderator once reminders are due, when the site is set to lock them. */
    public function isLockedFor(User $user): bool
    {
        return config('app.readererer_after_reminders') === 'lock'
            && $this->isPastReminderTime()
            && $user->role->value < UserRole::Moderator->value;
    }

    /** The concert ensembles' admins, or every ensemble's admins for a rehearsal. */
    public function attendanceListRecipients(): Collection
    {
        $ensembles = $this->is_concert ? $this->concert_ensembles : Ensemble::all();

        return $ensembles->load('admins')->flatMap->admins
            ->filter(fn (User $admin) => filled($admin->email))
            ->unique('id')
            ->values();
    }

    /** The members and van drivers of the date's setup group. */
    public function setupReminderRecipients(): Collection
    {
        if ($this->setup_group === null) {
            return collect();
        }

        return $this->setup_group->members
            ->merge($this->setup_group->van_drivers)
            ->filter(fn (User $member) => filled($member->email))
            ->unique('id')
            ->values();
    }

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
        ];
    }

    public function getNameAttribute(): string
    {
        if ($this->start_datetime->isSameDay($this->end_datetime)) {
            return $this->start_datetime->format('l, jS F Y').', '.$this->start_datetime->format('H:i').'–'.$this->end_datetime->format('H:i');
        }

        return $this->start_datetime->format('l, jS F Y, H:i').' – '.$this->end_datetime->format('l, jS F Y, H:i');
    }

    /** The date for its own table column; multi-day dates show the span. */
    public function getDateLabelAttribute(): string
    {
        if ($this->start_datetime->isSameDay($this->end_datetime)) {
            return $this->start_datetime->format('D, j M Y');
        }

        return $this->start_datetime->format('D, j M Y').' – '.$this->end_datetime->format('D, j M Y');
    }

    /** The time for its own table column. */
    public function getTimeLabelAttribute(): string
    {
        return $this->start_datetime->format('H:i').'–'.$this->end_datetime->format('H:i');
    }

    /** Date and time on one line, e.g. "Wed, 12 Aug 2026, 19:30–21:30"; multi-day dates spell out both ends. */
    public function getScheduleLabelAttribute(): string
    {
        if ($this->start_datetime->isSameDay($this->end_datetime)) {
            return $this->date_label.', '.$this->time_label;
        }

        return $this->start_datetime->format('D, j M Y, H:i').' – '.$this->end_datetime->format('D, j M Y, H:i');
    }

    /** How far off the date is, in words: "Today", "In 3 days", "2 months ago". */
    public function getRelativeLabelAttribute(): string
    {
        // Rounded, so a day an hour short across a DST change still counts as a whole day.
        $days = (int) round(now()->startOfDay()->diffInDays($this->start_datetime->copy()->startOfDay(), false));

        return match (true) {
            $days === 0 => __('Today'),
            $days === 1 => __('Tomorrow'),
            $days === -1 => __('Yesterday'),
            $days > 1 && $days < 14 => trans_choice('In :count day|In :count days', $days),
            $days < -1 && $days > -14 => trans_choice(':count day ago|:count days ago', abs($days)),
            default => ucfirst($this->start_datetime->diffForHumans(['parts' => 1])),
        };
    }
}
