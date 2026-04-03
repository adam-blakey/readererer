<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SDamian\Larasort\AutoSortable;

class Composer extends Model
{
    use HasFactory;
    use AutoSortable;

    protected $visible = [
        'first_name',
        'last_name',
        'created_at',
        'updated_at',
    ];

    public array $sortables = [
        'first_name',
        'last_name',
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

    public function pieces(): belongstoMany {
        return $this->belongsToMany(Piece::class);
    }

    protected function name(): Attribute {
        return Attribute::make(
            get: fn () => $this->first_name . ' ' . $this->last_name
        );
    }
}