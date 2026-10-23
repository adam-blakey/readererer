<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EnsembleAdmin extends Pivot
{
    use HasFactory;

    // A pivot names its table in the singular, and has neither an
    // auto-incrementing key nor timestamps by default; ensemble_admins has the
    // key, but no timestamp columns.
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
