<?php

namespace App\Models;

use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailLog extends Model
{
    protected $fillable = [
        'mailable_class',
        'mailable_args',
        'subject',
        'html_path',
        'status',
        'error_message',
    ];

    protected $casts = [
        'mailable_args' => 'array',
        'status' => EmailStatus::class,
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailRecipient::class);
    }
}
