<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserEnsemble extends Pivot
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ensembles()
    {
        return $this->HasMany(Ensemble::class, 'user_id', 'ensemble_id');
    }
}