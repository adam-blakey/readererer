<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ensemble extends Model
{
    use HasFactory;

    protected $name;
    protected $safe_name;
    protected $image;
    protected $visible;

    public function admins(): HasMany
    {
        return $this->hasMany(User::class);
    }
}