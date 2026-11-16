<?php

namespace App\Models;

use App\Attributes\Icon;
use App\Enums\Color;
use App\Enums\UserRole;
use App\Features\SetupGroupUsers;
use App\Traits\HasPropertyIcons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Pennant\Feature;
use SDamian\Larasort\AutoSortable;

#[Icon('arrow-badge-right', for: 'name')]
#[Icon('calendar', for: 'week')]
#[Icon('paint', for: 'color')]
#[Icon('mail', for: 'mailing_list_email')]
#[Icon('pencil-bolt', for: 'created_at')]
#[Icon('pencil-up', for: 'updated_at')]
class SetupGroup extends Model
{
    use AutoSortable;
    use HasFactory;
    use HasPropertyIcons;
    use SoftDeletes;

    protected $visible = [
        'name',
        'week',
        'color',
        'mailing_list_email',
        'van_drivers',
        'created_at',
        'updated_at',
    ];

    public array $sortables = [
        'name',
        'week',
        'color',
        'created_at',
        'updated_at',
    ];

    public function casts(): array
    {
        return [
            'color' => Color::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'name',
        'week',
        'color',
        'mailing_list_email',
        'van_drivers',
    ];

    public function getVisible(): array
    {
        $visible = config('app.readererer_van_drivers') ? $this->visible : array_values(array_diff($this->visible, ['van_drivers']));

        return Feature::active(SetupGroupUsers::class) ? [...$visible, 'members'] : $visible;
    }

    public function getLabelAttribute(): string
    {
        return trim((string) $this->name) !== ''
            ? $this->name
            : __('Setup group :week', ['week' => $this->week]);
    }

    /** The mailing list as a recipient for the email dispatcher, which sends to users. */
    public function mailingListRecipient(): ?User
    {
        return filled($this->mailing_list_email)
            ? new User(['first_name' => $this->label, 'last_name' => __('mailing list'), 'email' => $this->mailing_list_email, 'role' => UserRole::Guest])
            : null;
    }

    #[Icon('users')]
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    #[Icon('truck')]
    public function van_drivers(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'setup_group_van_driver', 'setup_group_id', 'user_id')
            ->orderBy('sort');
    }
}
