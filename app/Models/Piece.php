<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Piece extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function composer(): belongsTo {
        return $this->belongsTo(Composer::class);
    }

    public function parts(): HasMany {
        return $this->hasMany(Part::class);
    }

    public function parts_string(): string {
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