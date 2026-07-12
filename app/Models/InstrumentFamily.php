<?php

namespace App\Models;

use App\Attributes\Icon;
use App\Traits\HasPropertyIcons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;

#[Icon('guitar-pick', for: 'name')]
#[Icon('paint', for: 'color')]
#[Icon('pencil-bolt', for: 'created_at')]
#[Icon('pencil-up', for: 'updated_at')]
class InstrumentFamily extends Model
{
    use AutoSortable;
    use HasFactory;
    use HasPropertyIcons;
    use SoftDeletes;

    protected $visible = [
        'name',
        'color',
        'created_at',
        'updated_at',
    ];

    public array $sortables = [
        'name',
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
        'color',
    ];
}
