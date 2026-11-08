<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Traits\HasImage;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as SeatingPlanPdf;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;

class Ensemble extends Model
{
    use AutoSortable;
    use HasFactory;
    use HasImage;
    use SoftDeletes;

    protected $visible = [
        'image',
        'name',
        'slug',
        'show',
        'admins',
        'number_of_members',
        'created_at',
        'updated_at',
    ];

    public array $column_labels = [
        'number_of_members' => 'Members',
    ];

    public array $sortables = [
        'name',
        'slug',
        'show',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'admins',
    ];

    public function casts(): array
    {
        return [
            'show' => 'boolean',
            'seating_plan_enabled' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function admins(): BelongsToMany
    {

        return $this->belongsToMany(User::class, 'ensemble_admins', 'ensemble_id', 'admin_id')->orderBy('first_name');
    }

    /** Member count, excluding the generic ensemble login; uses a loaded count or relation when there is one. */
    public function getNumberOfMembersAttribute(): int
    {
        if (array_key_exists('users_count', $this->attributes)) {
            return (int) $this->attributes['users_count'];
        }

        if ($this->relationLoaded('users')) {
            return $this->users->count();
        }

        return $this->users()->count();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_ensemble')
            ->using(UserEnsemble::class)
            ->withPivot('instrument_family_id')
            ->withPivot('seat_column')
            ->withPivot('seat_row')
            ->where('role', '!=', UserRole::Ensemble)
            ->orderBy('first_name');
    }

    public function hasSeatingPlan(): bool
    {
        return $this->users()->wherePivotNotNull('seat_row')->exists();
    }

    public function seatingPlanPdf(TermDate $termDate): SeatingPlanPdf
    {
        $members = $this->users()
            ->wherePivotNotNull('instrument_family_id')
            ->with(['attendances' => fn ($query) => $query->where('term_date_id', $termDate->id)])
            ->get();

        $instrumentFamilies = InstrumentFamily::whereIn('id', $members->pluck('pivot.instrument_family_id'))->orderBy('name')->get()->keyBy('id');

        return Pdf::loadView('mail.seating-plan-pdf', ['ensemble' => $this, 'termDate' => $termDate, 'members' => $members, 'instrumentFamilies' => $instrumentFamilies])
            ->setPaper('a4', 'landscape');
    }
}
