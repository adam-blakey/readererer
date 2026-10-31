<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use SDamian\Larasort\AutoSortable;

class Term extends Model
{
    use AutoSortable;
    use HasFactory;
    use SoftDeletes;

    protected $visible = [
        'name',
        'slug',
        'number_of_rehearsals',
        'number_of_concerts',
        'earliest_date',
        'latest_date',
        'created_at',
        'updated_at',
    ];

    public array $column_labels = [
        'number_of_rehearsals' => 'Rehearsals',
        'number_of_concerts' => 'Concerts',
        'earliest_date' => 'First date',
        'latest_date' => 'Last date',
    ];

    public array $sortables = [
        'name',
        'slug',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'slug',
        'term_dates',
    ];

    public function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'earliest_date' => 'datetime',
            'latest_date' => 'datetime',
        ];
    }

    public function term_dates(): HasMany
    {
        return $this->hasMany(TermDate::class)->orderBy('start_datetime');
    }

    public function getNumberOfRehearsalsAttribute(): int
    {
        return $this->term_dates->whereNull('concert_ensemble_id')->count();
    }

    public function getNumberOfConcertsAttribute(): int
    {
        return $this->term_dates->whereNotNull('concert_ensemble_id')->count();
    }

    public function getNumberOfTermDatesAttribute(): int
    {
        return $this->term_dates->count();
    }

    public function getEarliestDateAttribute(): ?Carbon
    {
        return $this->term_dates->first()?->start_datetime;
    }

    public function getLatestDateAttribute(): ?Carbon
    {
        return $this->term_dates->last()?->start_datetime;
    }

    public function getFormattedTermDateRangeAttribute(): string
    {
        if ($this->term_dates->count() === 0) {
            return '–';
        } else {
            $first = $this->earliestDate;
            $last = $this->latestDate;

            $months = $first->diff($last)->months;

            if ($months == 0) {
                if ($first->diff($last)->day == 0) {
                    return $first->format('Y-m-d');
                }

                return 'less than a month, '.$first->format('Y-m-d').' to '.$last->format('Y-m-d');
            }

            return 'about '.$months.' months, '.$first->format('Y-m-d').' to '.$last->format('Y-m-d');
        }
    }
}
