<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TermDate extends Model
{
    use HasFactory;

    protected $start_datetime;
    protected $end_datetime;
    protected $is_concert;
    protected $visible;

    public function term(): HasOne
    {
        return $this->hasOne(Term::class);
    }

    public function setup_group(): HasOne
    {
        return $this->hasOne(SetupGroup::class);
    }
}