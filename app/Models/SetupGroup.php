<?php

namespace App\Models;

use App\Attributes\Icon;
use App\Traits\HasPropertyIcons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;

#[Icon('arrow-badge-right', for: 'name')]
#[Icon('calendar', for: 'week')]
#[Icon('paint', for: 'color')]
#[Icon('pencil-bolt', for: 'created_at')]
#[Icon('pencil-up', for: 'updated_at')]
class SetupGroup extends Model
{
    use AutoSortable;
    use HasFactory;
    use HasPropertyIcons;
    use SoftDeletes;

    protected $visible = [
        'name',
        'week',
        'color',
        'van_drivers',
        'created_at',
        'updated_at',
    ];

    public array $sortables = [
        'name',
        'week',
        'color',
        'created_at',
        'updated_at',
    ];

    public function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'name',
        'week',
        'color',
        'van_drivers',
    ];

    #[Icon('users')]
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    #[Icon('truck')]
    public function van_drivers(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'setup_group_van_driver', 'setup_group_id', 'user_id')
            ->orderBy('sort');
    }
}
