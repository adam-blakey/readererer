<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserRole;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $deleted_at;
    protected $avatar;

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function ensembles()
    {
        return $this->belongsToMany(Ensemble::class, 'user_ensemble', 'user_id', 'ensemble_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function getInitialsAttribute(): string
    {
        $name = explode(' ', $this->name);
        $initials = '';
        foreach ($name as $n) {
            $initials .= $n[0];
        }
        return $initials;
    }
}