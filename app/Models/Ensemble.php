<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;

class Ensemble extends Model
{
    use HasFactory;
    use SoftDeletes;
    use AutoSortable;

    protected $name;
    protected $slug;
    protected $image;

    private array $sortables = [
        'name',
        'slug',
        'created_at',
        'updated_at',
    ];

    public function admins(): BelongsToMany
    {
        //return $this->hasManyThrough(User::class, EnsembleAdmin::class, 'ensemble_id', 'ensemble_admin_id');

        return $this->belongsToMany(User::class, 'ensemble_admins', 'ensemble_id', 'admin_id')->orderBy('first_name');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_ensemble', 'ensemble_id', 'user_id')->orderBy('first_name');
    }
}