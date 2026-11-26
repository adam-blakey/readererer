<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Features\FairVanDriverRota;
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
use Laravel\Pennant\Feature;

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
        'time_tbc',
        'location_id',
        'description',
        'concert_order',
        'dress_code_id',
    ];

    /** A date whose time is to be confirmed starts and ends at midnight, so its reminders go out relative to the start of the day. */
    protected static function booted(): void
    {
        static::saving(function (TermDate $termDate) {
            if ($termDate->time_tbc) {
                $termDate->start_datetime = $termDate->start_datetime->copy()->startOfDay();
                $termDate->end_datetime = $termDate->end_datetime->copy()->startOfDay();
            }
        });
    }

    public function term(): BelongsTo
    {
        return $this->BelongsTo(Term::class);
    }

    public function setup_group(): BelongsTo
    {
        return $this->belongsTo(SetupGroup::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function dress_code(): BelongsTo
    {
        return $this->belongsTo(DressCode::class)->withTrashed();
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

        $drivers = $this->setup_group->van_drivers->values();

        if ($drivers->isEmpty()) {
            return null;
        }

        if (! Feature::for(null)->active(FairVanDriverRota::class)) {
            $previousCount = TermDate::where('setup_group_id', $this->setup_group_id)
                ->where('start_datetime', '<', $this->start_datetime)
                ->count();

            return $drivers->get($previousCount % $drivers->count());
        }

        $drives = [];
        $lastDrove = [];
        // A driver added to the group (or added back) joins level with the least-used driver, rather than catching up on dates from before they joined.
        $join = function (Carbon $at) use ($drivers, &$drives): void {
            foreach ($drivers as $driver) {
                if (! isset($drives[$driver->id]) && ($driver->pivot->created_at === null || $driver->pivot->created_at->lte($at))) {
                    $drives[$driver->id] = $drives === [] ? 0 : min($drives);
                }
            }
        };
        $next = function () use ($drivers, &$drives, &$lastDrove): ?int {
            return $drivers->filter(fn (User $driver) => isset($drives[$driver->id]))
                ->sortBy(fn (User $driver, int $sort) => [$drives[$driver->id], $lastDrove[$driver->id] ?? -1, $sort])
                ->first()?->id;
        };

        $earlier = TermDate::where('setup_group_id', $this->setup_group_id)
            ->where(fn ($query) => $query->where('start_datetime', '<', $this->start_datetime)
                ->orWhere(fn ($query) => $query->where('start_datetime', $this->start_datetime)->where('id', '<', $this->id ?? 0)))
            ->orderBy('start_datetime')
            ->orderBy('id')
            ->get(['start_datetime', 'van_driver_id']);

        foreach ($earlier as $index => $date) {
            $join($date->start_datetime);
            $driverId = $date->van_driver_id ?? $next();
            if ($driverId !== null && isset($drives[$driverId])) {
                $drives[$driverId]++;
                $lastDrove[$driverId] = $index;
            }
        }
        $join($this->start_datetime);

        return $drivers->firstWhere('id', $next());
    }

    public function concert_ensembles(): BelongsToMany
    {
        return $this->belongsToMany(Ensemble::class, 'concert_ensemble')->orderBy('name');
    }

    public function getIsConcertAttribute(): bool
    {
        return $this->concert_ensembles->isNotEmpty();
    }

    /** The names of the concert's shown ensembles. */
    public function getConcertEnsembleNamesAttribute(): string
    {
        return $this->concert_ensembles->where('show', true)->pluck('name')->join(', ');
    }

    public function getConcertEnsembleShortNamesAttribute(): string
    {
        return $this->concert_ensembles->where('show', true)->pluck('short_label')->join(', ');
    }

    /** A site with a single ensemble need not say who plays the concert. */
    public function getConcertLabelAttribute(): string
    {
        $names = config('app.readererer_single_ensemble') ? '' : $this->concert_ensemble_short_names;

        return $names === '' ? __('Concert') : __('Concert (:ensembles)', ['ensembles' => $names]);
    }

    public function email_logs(): HasMany
    {
        return $this->hasMany(EmailLog::class)->latest();
    }

    public function timesSent(string $mailableClass): int
    {
        return $this->email_logs
            ->where('mailable_class', $mailableClass)
            ->filter->wasDelivered()
            ->count();
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

    /** When the automatic reminders go out, or null when they are turned off. Concerts can have their own lead time. */
    public function remindersDueAt(): ?Carbon
    {
        $minutes = (int) (($this->is_concert ? config('app.readererer_concert_reminder_minutes') : null) ?: config('app.readererer_reminder_minutes'));

        return $minutes > 0 ? $this->start_datetime->copy()->subMinutes($minutes) : null;
    }

    public function isPastReminderTime(): bool
    {
        return $this->remindersDueAt()?->isPast() ?? false;
    }

    /** Polls lock for everyone below moderator once the date has started, or once reminders are due when the site is set to lock them. */
    public function isLockedFor(User $user): bool
    {
        return $user->role->value < UserRole::Moderator->value
            && ($this->start_datetime->isPast() || $this->isLockedByReminders());
    }

    public function isLockedByReminders(): bool
    {
        return config('app.readererer_after_reminders') === 'lock' && $this->isPastReminderTime();
    }

    /** The concert's ensembles, or every ensemble for a rehearsal, less any that get no emails. */
    public function notifiedEnsembles(): Collection
    {
        return $this->applicableEnsembles()->reject->getsNoEmails()->values();
    }

    /** True when every ensemble the date is for gets no emails, so nothing is sent for it. */
    public function sendsNoEmails(): bool
    {
        $ensembles = $this->applicableEnsembles();

        return $ensembles->isNotEmpty() && $ensembles->every->getsNoEmails();
    }

    private function applicableEnsembles(): Collection
    {
        return $this->is_concert ? $this->concert_ensembles : Ensemble::all();
    }

    /** The concert ensembles' admins, or every ensemble's admins for a rehearsal. */
    public function attendanceListRecipients(): Collection
    {
        return $this->notifiedEnsembles()->load('admins')->flatMap->admins
            ->filter(fn (User $admin) => filled($admin->email))
            ->unique('id')
            ->values();
    }

    /** The members and van drivers of the date's setup group, and its mailing list. */
    public function setupReminderRecipients(): Collection
    {
        if ($this->setup_group === null) {
            return collect();
        }

        return $this->setup_group->members
            ->merge($this->setup_group->van_drivers)
            ->push($this->setup_group->mailingListRecipient())
            ->filter(fn (?User $member) => filled($member?->email))
            ->unique(fn (User $member) => strtolower($member->email))
            ->values();
    }

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'time_tbc' => 'boolean',
        ];
    }

    public function getNameAttribute(): string
    {
        if ($this->time_tbc) {
            return $this->start_datetime->isSameDay($this->end_datetime)
                ? $this->start_datetime->format('l, jS F Y').', '.__('time TBC')
                : $this->start_datetime->format('l, jS F Y').' – '.$this->end_datetime->format('l, jS F Y').', '.__('time TBC');
        }

        if ($this->start_datetime->isSameDay($this->end_datetime)) {
            return $this->start_datetime->format('l, jS F Y').', '.$this->start_datetime->format(time_format()).'–'.$this->end_datetime->format(time_format());
        }

        return $this->start_datetime->format('l, jS F Y, '.time_format()).' – '.$this->end_datetime->format('l, jS F Y, '.time_format());
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
        if ($this->time_tbc) {
            return __('TBC');
        }

        return $this->start_datetime->format(time_format()).'–'.$this->end_datetime->format(time_format());
    }

    /** Date and time on one line, e.g. "Wed, 12 Aug 2026, 19:30–21:30"; multi-day dates spell out both ends. */
    public function getScheduleLabelAttribute(): string
    {
        if ($this->time_tbc || $this->start_datetime->isSameDay($this->end_datetime)) {
            return $this->date_label.', '.$this->time_label;
        }

        return $this->start_datetime->format('D, j M Y, '.time_format()).' – '.$this->end_datetime->format('D, j M Y, '.time_format());
    }

    /** The date's own location, else the term's default. */
    public function getInferredLocationAttribute(): ?Location
    {
        return $this->location ?? $this->term?->location;
    }

    public function getLocationLabelAttribute(): string
    {
        return $this->inferred_location?->name ?? __('TBC');
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
