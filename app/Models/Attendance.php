<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\AttendanceStatus;

class Attendance extends Model
{
    use HasFactory;

    protected $edit_datetime;
    protected $edit_ip;


    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function edit_user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function term_date(): HasOne
    {
        return $this->hasOne(TermDate::class);
    }

    public function ensemble(): HasOne
    {
        return $this->hasOne(Ensemble::class);
    }

    public function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }
}