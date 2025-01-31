<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermDate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected Carbon $start_datetime;
    protected Carbon $end_datetime;
    protected bool $is_concert;

    public function term(): BelongsTo
    {
        return $this->BelongsTo(Term::class);
    }

    public function setup_group(): HasOne
    {
        return $this->hasOne(SetupGroup::class);
    }

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'is_concert' => 'boolean',
            'show' => 'boolean',
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->start_datetime->format('Y-m-d H:i') . ' - ' . $this->end_datetime->format('Y-m-d H:i');
    }
}