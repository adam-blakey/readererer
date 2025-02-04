<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Piece extends Model
{
    use HasFactory;
    use SoftDeletes;
    use AutoSortable;

    protected $name;

    protected $visible = [
        'name',
        'composer',
        'parts_string',
        'created_at',
        'updated_at'
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
    public function composer(): belongsTo {
        return $this->belongsTo(Composer::class);
    }

    public function parts(): HasMany {
        return $this->hasMany(Part::class);
    }

    public function getPartsStringAttribute(): string {
        $list_of_parts = "";
        for ($i = 0; $i < $this->parts->count(); $i++) {
            $list_of_parts .= $this->parts[$i]->name;
            if ($i != $this->parts->count() - 1) {
                $list_of_parts .= ", ";
            }
        }
        return $list_of_parts;
    }

    public function setlists(): BelongsToMany {
        return $this->belongsToMany(Setlist::class, 'setlist_piece');
    }
}