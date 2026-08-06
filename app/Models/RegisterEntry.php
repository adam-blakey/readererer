<?php

namespace App\Models;

use App\Enums\RegisterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One member's actual attendance at one rehearsal or concert.
 *
 * The attendance poll records what members said they would do in advance and is
 * append-only; the register records what happened on the day and is held as a
 * single current row per member per date.
 */
class RegisterEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'term_date_id',
        'ensemble_id',
        'status',
        'notes',
        'marked_by_user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function term_date(): BelongsTo
    {
        return $this->belongsTo(TermDate::class);
    }

    public function ensemble(): BelongsTo
    {
        return $this->belongsTo(Ensemble::class);
    }

    public function marked_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    public function casts(): array
    {
        return [
            'status' => RegisterStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getStatusTextAttribute(): string
    {
        return $this->status->label();
    }
}
