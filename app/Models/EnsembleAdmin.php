<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EnsembleAdmin extends Pivot
{
    public function ensemble()
    {
        return $this->belongsTo(Ensemble::class);
    }

    public function admins()
    {
        return $this->HasMany(User::class, 'ensemble_id', 'admin_id');
    }
}