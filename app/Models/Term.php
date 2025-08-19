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
        'earliest_date',
        'latest_date',
        'created_at',
        'updated_at',
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

    public function getEarliestDateAttribute(): Carbon
    {
        $earliest_termdate = $this->term_dates()->orderBy('start_datetime', 'asc')->firstOrFail();

        return $earliest_termdate->start_datetime;
    }

    public function getLatestDateAttribute(): Carbon
    {
        $latest_termdate = $this->term_dates()->orderBy('start_datetime', 'desc')->firstOrFail();

        return $latest_termdate->end_datetime;
    }

    public function getFormattedTermDateRangeAttribute(): string
    {
        if ($this->term_dates_count === 0)
        {
            return '–';
        }
        else
        {
            $first = $this->getEarliestDateAttribute();
            $last = $this->getLatestDateAttribute();

            // Get human-readable length.
            $length = $first->diff($last)->months;

            return 'about ' . $length . ' months, ' . $first->format('Y-m-d') . ' to ' . $last->format('Y-m-d');
        }
    }
}
