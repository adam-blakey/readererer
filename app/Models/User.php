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
        $address = [];

        if ($this->address_line1) {
            $address[] = $this->address_line1;
        }

        if ($this->address_line2) {
            $address[] = $this->address_line2;
        }

        if ($this->address_city) {
            $address[] = $this->address_city;
        }

        if ($this->address_post_code) {
            $address[] = $this->address_post_code;
        }

        return implode(', ', $address);
    }

    public function getIsOver18Attribute(): bool
    {
        if ($this->date_of_birth === null) {
            return false;
        }

        return $this->date_of_birth->diffInYears(SupportCarbon::now()) >= 18;
    }

    public function getEmergencyContactDetailsAttribute(): string
    {
        $contact_details = [];

        if ($this->emergency_contact_name) {
            $contact_details[] = $this->emergency_contact_name;
        }

        if ($this->emergency_contact_number) {
            $contact_details[] = $this->emergency_contact_number;
        }

        if ($this->emergency_contact_relationship) {
            $contact_details[] = $this->emergency_contact_relationship;
        }

        if ($this->emergency_contact_address_line1) {
            $contact_details[] = $this->emergency_contact_address_line1;
        }

        if ($this->emergency_contact_address_line2) {
            $contact_details[] = $this->emergency_contact_address_line2;
        }

        if ($this->emergency_contact_address_city) {
            $contact_details[] = $this->emergency_contact_address_city;
        }

        if ($this->emergency_contact_address_post_code) {
            $contact_details[] = $this->emergency_contact_address_post_code;
        }

        return implode(', ', $contact_details);
    }

    public function getNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}