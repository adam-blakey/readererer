<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Term extends Model
{
    use HasFactory;

    protected string $name;
    protected string $slug;
    protected string $image;
    protected bool $show;

    public function term_dates(): HasMany
    {
        return $this->hasMany(TermDate::class);
    }

    public function earliest_date(): Carbon
    {
        $earliest_termdate = $this->term_dates()->orderBy('start_datetime', 'asc')->firstOrFail();

        return $earliest_termdate->start_datetime;
    }

    public function latest_date(): Carbon
    {
        $latest_termdate = $this->term_dates()->orderBy('start_datetime', 'desc')->firstOrFail();

        return $latest_termdate->end_datetime;
    }

    public function formatted_term_date_range(): string
    {
        if ($this->term_dates_count === 0)
        {
            return '–';
        }
        else
        {
            $first = $this->earliest_date();
            $last = $this->latest_date();

            // Get human-readable length.
            $length = $first->diff($last)->months;

            return 'about ' . $length . ' months, ' . $first->format('Y-m-d') . ' to ' . $last->format('Y-m-d');
        }
    }
}