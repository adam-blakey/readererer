<?php

namespace App\Models;

use App\Attributes\Icon;
use App\Enums\UserRole;
use App\Traits\HasImage;
use App\Traits\HasPropertyIcons;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as SeatingPlanPdf;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;

#[Icon('abc', for: 'short_name')]
class Ensemble extends Model
{
    use AutoSortable;
    use HasFactory;
    use HasImage;
    use HasPropertyIcons;
    use SoftDeletes;

    protected $visible = [
        'image',
        'name',
        'short_name',
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
        'short_name',
        'slug',
        'show',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'short_name',
        'admins',
    ];

    protected $attributes = [
        'show' => true,
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

    /** The name for tight places such as tables: the short name, falling back to the full name. */
    public function getShortLabelAttribute(): string
    {
        return $this->short_name ?? $this->name;
    }

    /** Hidden ensembles are left out of listings; the ensembles list and their own pages still show them. */
    #[Scope]
    protected function shown(Builder $query): void
    {
        $query->where('ensembles.show', true);
    }

    public function getsNoEmails(): bool
    {
        return ! $this->show;
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

    /** Loads the members with their setup groups and each membership's instrument family, for pages that list them. */
    public function loadMembers(): static
    {
        $this->load('users.setup_group');
        (new Collection($this->users->pluck('pivot')))->load('instrumentFamily');

        return $this;
    }

    public function hasSeatingPlan(): bool
    {
        return $this->users()->wherePivotNotNull('seat_row')->exists();
    }

    public function seatingPlanPdf(?TermDate $termDate = null): SeatingPlanPdf
    {
        $members = $this->users()
            ->wherePivotNotNull('instrument_family_id')
            ->when($termDate, fn ($query) => $query->with(['attendances' => fn ($query) => $query->where('term_date_id', $termDate->id)]))
            ->get();

        $instrumentFamilies = InstrumentFamily::whereIn('id', $members->pluck('pivot.instrument_family_id'))->orderBy('name')->get()->keyBy('id');

        return Pdf::loadView('mail.seating-plan-pdf', ['ensemble' => $this, 'termDate' => $termDate, 'members' => $members, 'instrumentFamilies' => $instrumentFamilies])
            ->setPaper('a4', 'landscape');
    }
}
