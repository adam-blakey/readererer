<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'term_date_id',
        'ensemble_id',
        'status',
        'dep_name',
        'edit_user_id',
        'edit_ip',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function edit_user(): BelongsTo
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

    #[Scope]
    protected function latestUpdates(Builder $query): void
    {
        $query->with(['user', 'edit_user', 'term_date'])
            ->whereRelation('user', 'deleted_at', null)
            ->whereRelation('edit_user', 'deleted_at', null)
            ->whereRelation('term_date', 'deleted_at', null)
            ->whereRelation('term_date.term', 'deleted_at', null)
            ->whereRelation('ensemble', 'deleted_at', null)
            ->orderBy('created_at', 'DESC');
    }

    public function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->ensemble?->name.': '.$this->term_date?->term?->name;
    }

    public function getStatusTextAttribute(): string
    {
        $display_status = $this->status->displayed();

        return $display_status === AttendanceStatus::Depping && $this->dep_name
            ? __('Sending a dep: :name', ['name' => $this->dep_name])
            : $display_status->label();
    }
}
