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

    public function full_name($reverse = false): string {
        if ($reverse) {
            return "{$this->last_name}, {$this->first_name}";
        }
        else {
            return "{$this->first_name} {$this->last_name}";
        }
    }

    public function initials(): string {
        return "{$this->first_name[0]}{$this->last_name[0]}";
    }
}
