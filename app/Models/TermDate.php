<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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

    public function isConcert(): bool
    {
        return $this->concert_ensemble_id !== null;
    }

    /**
     * The ensembles playing at this date: just the concert ensemble for a
     * concert, or every ensemble for a shared rehearsal.
     *
     * Pass $ensembles to pick from an already-loaded set of ensembles rather
     * than query for them again — handy when several dates are being listed
     * at once.
     */
    public function playing_ensembles(?Collection $ensembles = null): Collection
    {
        $ensembles ??= Ensemble::orderBy('name')->get();

        if ($this->isConcert()) {
            return $ensembles->where('id', $this->concert_ensemble_id)->values();
        }

        return $ensembles->values();
    }

    /**
     * The members playing at this date, in name order and with everything the
     * attendance views need (their memberships, attendance and setup group)
     * already loaded.
     */
    public function players(): Collection
    {
        $ensemble_ids = $this->playing_ensembles()->pluck('id');

        return User::whereHas('ensembles', fn (Builder $query) => $query->whereIn('ensembles.id', $ensemble_ids))
            ->where('role', '!=', UserRole::Ensemble)
            ->with(['attendances', 'ensembles', 'setup_group'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
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
            return $this->start_datetime->format('l, jS F Y').'  '.$this->start_datetime->format('H:i').'-'.$this->end_datetime->format('H:i');
        }

        return $this->start_datetime->format('l, jS F Y H:i').' - '.$this->end_datetime->format('l, jS F Y H:i');
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
}
