<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasPropertyIcons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Str;
use SDamian\Larasort\AutoSortable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, AutoSortable;

    protected $deleted_at;
    protected $image;

    protected $visible = [
        'image',
        'name',
        'first_name',
        'last_name',
        'username',
        'email',
        'role',
        'setup_group',
        'is_over_18',
        'created_at',
        'updated_at',
    ];

    public array $sortables = [
        'first_name',
        'last_name',
        'username',
        'email',
        'role',
        'created_at',
        'updated_at',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function ensembles()
    {
        return $this->belongsToMany(Ensemble::class, 'user_ensemble')
            ->using(UserEnsemble::class)
            ->withPivot('instrument_family_id')
            ->withPivot('seat_column')
            ->withPivot('seat_row');
    }

    public function setup_group()
    {
        return $this->belongsTo(SetupGroup::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'image',
        'role',
        'setup_group',
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
    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'date_of_birth' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'is_over_18' => 'boolean',
        ];
    }

    /**
     * Build a username from a name that is unique across all users.
     *
     * The base is the dotted slug of the name (e.g. "john.smith"). If that is
     * already taken a numeric suffix is appended ("john.smith.2", "john.smith.3",
     * ...). Soft-deleted users are considered too, since the `username` column is
     * uniquely constrained and their rows still occupy the value.
     */
    public static function generateUniqueUsername(string $firstName, string $lastName): string
    {
        $base = Str::slug($firstName . ' ' . $lastName, '.');
        $username = $base;
        $suffix = 1;

        while (static::withTrashed()->where('username', $username)->exists()) {
            $suffix++;
            $username = $base . '.' . $suffix;
        }

        return $username;
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
            case UserRole::Ensemble:
                return 'Ensemble';
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

    public function getFirstNameInitialAttribute(): string
    {
        return $this->first_name[0];
    }

    public function getLastNameInitialAttribute(): string
    {
        return $this->last_name[0];
    }

    public function membership($ensemble): string {
        $membership = $this->ensembles->firstWhere('id', $ensemble->id);
        if (!$membership) {
            return "Not a member";
        }

        $pivot = $membership->pivot;
        $instrument_name = $pivot->instrumentFamily?->name ?? '';
        $seat_name = $pivot->seat_row . $pivot->seat_column;

        if (!$instrument_name && !$seat_name) {
            return "No membership information";
        }
        elseif ($instrument_name && !$seat_name) {
            return $instrument_name;
        }
        elseif (!$instrument_name && $seat_name) {
            return $seat_name;
        }

        return $seat_name . " in " . $instrument_name;
    }
}
