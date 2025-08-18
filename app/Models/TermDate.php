<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    protected $fillable = [
        'start_datetime',
        'end_datetime',
        'concert_ensemble_id',
    ];

    public function term(): BelongsTo
    {
        return $this->BelongsTo(Term::class);
    }

    public function setup_group(): HasOne
    {
        return $this->hasOne(SetupGroup::class);
    }

    public function concert_ensemble(): BelongsTo
    {
        return $this->belongsTo(Ensemble::class, 'concert_ensemble_id');
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
        if ($this->start_datetime->isSameDay($this->end_datetime))
        {
            return $this->start_datetime->format('l, jS F Y') . '  ' . $this->start_datetime->format('H:i') . '-' . $this->end_datetime->format('H:i');
        }
        return $this->start_datetime->format('l, jS F Y H:i') . ' - ' . $this->end_datetime->format('l, jS F Y H:i');
    }
}
