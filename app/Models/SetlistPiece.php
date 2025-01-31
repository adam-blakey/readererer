<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SetlistPiece extends Pivot
{
    public function setlist()
    {
        return $this->belongsTo(Setlist::class);
    }

    public function piece()
    {
        return $this->belongsTo(Piece::class);
    }
}