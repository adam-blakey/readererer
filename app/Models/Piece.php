<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Piece extends Model
{
    use HasFactory;

    public function composer(): belongsTo {
        return $this->belongsTo(Composer::class);
    }

    public function parts(): HasMany {
        return $this->hasMany(Part::class);
    }
}
