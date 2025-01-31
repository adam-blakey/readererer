<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setlist extends Model
{
    use HasFactory;

    public function pieces()
    {
        return $this->belongsToMany(Piece::class, 'setlist_piece')
            ->withPivot('order');
    }

    public function concerts()
    {
        return $this->belongsToOne(TermDate::class);
    }
}
