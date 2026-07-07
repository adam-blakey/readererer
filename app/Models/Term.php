<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use SDamian\Larasort\AutoSortable;

class Term extends Model
{
    use HasFactory;
    use SoftDeletes;
    use AutoSortable;

    protected string $name;
    protected string $slug;
    protected string $image;

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

    // Friendlier, shorter labels for the wordier columns when rendered in tables.
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
        'term_dates'
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
        return $this->term_dates()->whereNull('concert_ensemble_id')->count();
    }

    public function getNumberOfConcertsAttribute(): int
    {
        return $this->term_dates()->whereNotNull('concert_ensemble_id')->count();
    }

    public function getNumberOfTermDatesAttribute(): int
    {
        return $this->term_dates()->count();
    }

    public function getEarliestDateAttribute(): ?Carbon
    {
        $earliest_termdate = $this
            ->term_dates()
            ->orderBy('start_datetime', 'asc')
            ->first();

        return $earliest_termdate?->start_datetime;
    }

    public function getLatestDateAttribute(): ?Carbon
    {
        $number_of_termdates = $this->term_dates()->count();
        $latest_termdate = $this
            ->term_dates()
            ->orderBy('start_datetime', 'desc')
            ->skip($number_of_termdates - 1)
            ->first();

        return $latest_termdate?->start_datetime;
    }

    public function getFormattedTermDateRangeAttribute(): string
    {
        if ($this->term_dates->count() === 0)
        {
            return '–';
        }
        else
        {
            $first = $this->earliestDate;
            $last = $this->latestDate;

            // Get human-readable length.
            $months = $first->diff($last)->months;

            if ($months == 0)
            {
                if ($first->diff($last)->day == 0)
                {
                    return $first->format('Y-m-d');
                }

                return 'less than a month, ' . $first->format('Y-m-d') . ' to ' . $last->format('Y-m-d');
            }

            return 'about ' . $months . ' months, ' . $first->format('Y-m-d') . ' to ' . $last->format('Y-m-d');
        }
    }
}
