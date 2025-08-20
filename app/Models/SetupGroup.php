<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SetupGroup extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $name;
    protected $week;
    protected $color;

    protected $fillable = [
        'name',
        'week',
        'color',
    ];

    public function vanDrivers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'setup_group_van_driver', 'setup_group_id', 'user_id')->orderBy('first_name');
    }
}
