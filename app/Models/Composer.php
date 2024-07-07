<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Composer extends Model
{
    use HasFactory;

    public function pieces(): belongstoMany {
        return $this->belongsToMany(Piece::class);
    }
}
