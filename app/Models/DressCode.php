<?php

namespace App\Models;

use App\Attributes\Icon;
use App\Traits\HasPropertyIcons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;

#[Icon('shirt', for: 'name')]
#[Icon('align-left', for: 'description')]
#[Icon('pencil-bolt', for: 'created_at')]
#[Icon('pencil-up', for: 'updated_at')]
class DressCode extends Model
{
    use AutoSortable;
    use HasFactory;
    use HasPropertyIcons;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $visible = [
        'name',
        'description',
        'created_at',
        'updated_at',
    ];

    public array $sortables = [
        'name',
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

    public function term_dates(): HasMany
    {
        return $this->hasMany(TermDate::class);
    }
}
