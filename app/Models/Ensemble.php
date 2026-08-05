<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;

class Ensemble extends Model
{
    use AutoSortable;
    use HasFactory;
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

    // Friendlier, shorter label for the computed member-count column.
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
        // return $this->hasManyThrough(User::class, EnsembleAdmin::class, 'ensemble_id', 'ensemble_admin_id');

        return $this->belongsToMany(User::class, 'ensemble_admins', 'ensemble_id', 'admin_id')->orderBy('first_name');
    }

    /**
     * Number of members in the ensemble (excluding the generic ensemble login).
     *
     * Prefers an eager-loaded aggregate (`withCount('users')`) or an already
     * loaded relation so index listings don't fire a query per row.
     */
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
}
