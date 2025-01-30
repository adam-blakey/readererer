<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;

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
        'first_name',
        'last_name',
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
            'date_of_birth' => 'date',
        ];
    }

    public function getInitialsAttribute(): string
    {
        return $this->first_name[0] . $this->last_name[0];
    }

    public function getRoleDescriptionAttribute(): string
    {
        switch ($this->role) {
            case UserRole::Admin:
                return 'Admin';
            case UserRole::Moderator:
                return 'Moderator';
            case UserRole::Member:
                return 'Member';
            case UserRole::Guest:
                return 'Guest';
            default:
                return 'Unknown';
        }
    }

    public function getFullAddressAttribute(): string
    {
        return $this->address_line1 . ', ' . $this->address_line2 . ', ' . $this->address_city . ', ' . $this->address_post_code;
    }

    public function getIsOver18Attribute(): bool
    {
        return $this->date_of_birth->diffInYears(SupportCarbon::now()) >= 18;
    }

    public function getEmergencyContactDetailsAttribute(): string
    {
        return $this->emergency_contact_name . ', ' . $this->emergency_contact_number . ', ' . $this->emergency_contact_relationship . ', ' . $this->emergency_contact_address_line1 . ', ' . $this->emergency_contact_address_line2 . ', ' . $this->emergency_contact_address_city . ', ' . $this->emergency_contact_address_post_code;
    }

    public function getNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
