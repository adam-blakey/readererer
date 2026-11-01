<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EnsembleAdmin extends Pivot
{
    use HasFactory;

    // Pivot defaults assume a singular table name, no incrementing key and timestamps.
    protected $table = 'ensemble_admins';

    public $incrementing = true;

    public $timestamps = false;

    public function ensemble()
    {
        return $this->belongsTo(Ensemble::class);
    }

    public function admins()
    {
        return $this->HasMany(User::class, 'ensemble_id', 'admin_id');
    }
}
