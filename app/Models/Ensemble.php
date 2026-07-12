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
        'created_at',
        'updated_at',
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
