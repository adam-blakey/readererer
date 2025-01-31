<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserEnsemble extends Pivot
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ensemble()
    {
        return $this->belongsTo(Ensemble::class);
    }

    public function instrumentFamily()
    {
        return $this->belongsTo(InstrumentFamily::class);
    }

    public function getSeatAttribute()
    {
        return $this->seat_column . $this->seat_row;
    }
}