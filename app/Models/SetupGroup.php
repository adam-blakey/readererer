<?php

namespace App\Models;

use App\Attributes\Icon;
use App\Traits\HasPropertyIcons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;

class SetupGroup extends Model
{
    use HasFactory;
    use SoftDeletes;
    use AutoSortable;
    use HasPropertyIcons;

    #[Icon('arrow-badge-right')]
    protected $name;
    #[Icon('calendar')]
    protected $week;
    #[Icon('paint')]
    protected $color;
    #[Icon('pencil-bolt')]
    protected $created_at;
    #[Icon('pencil-up')]
    protected $updated_at;

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
    ];

    #[Icon('truck')]
    public function van_drivers(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'setup_group_van_driver', 'setup_group_id', 'user_id')
            ->orderBy('sort');
    }
}
