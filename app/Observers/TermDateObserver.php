<?php

namespace App\Observers;

use App\Mail\RosterChangedMail;
use App\Models\SetupGroup;
use App\Models\TermDate;
use App\Models\User;
use App\Services\EmailDispatcher;
use Illuminate\Support\Collection;

class TermDateObserver
{
    public function __construct(private EmailDispatcher $dispatcher) {}

    /**
     * When the setup group or van driver of an upcoming date changes, alert
     * everyone affected (members and drivers of the old and new groups, and
     * the old and new explicitly-assigned drivers).
     */
    public function updated(TermDate $termDate): void
    {
        $groupChanged = $termDate->wasChanged('setup_group_id');
        $driverChanged = $termDate->wasChanged('van_driver_id');

        if (! $groupChanged && ! $driverChanged) {
            return;
        }

        if ($termDate->start_datetime === null || $termDate->start_datetime->isPast()) {
            return;
        }

        $oldGroup = $groupChanged ? SetupGroup::withTrashed()->find($termDate->getOriginal('setup_group_id')) : $termDate->setup_group;
        $newGroup = $termDate->setup_group;
        $oldDriver = $driverChanged ? User::withTrashed()->find($termDate->getOriginal('van_driver_id')) : $termDate->van_driver;
        $newDriver = $termDate->van_driver;

        $changes = [];

        if ($groupChanged) {
            $changes[] = 'The setup group is now '.($newGroup?->name ?? 'unassigned')
                .' (was '.($oldGroup?->name ?? 'unassigned').').';
        }

        if ($driverChanged) {
            $changes[] = 'The van driver is now '.($newDriver?->name ?? 'unassigned')
                .' (was '.($oldDriver?->name ?? 'unassigned').').';
        }

        $recipients = $this->affectedUsers([$oldGroup, $newGroup], [$oldDriver, $newDriver]);

        if ($recipients->isEmpty()) {
            return;
        }

        $this->dispatcher->send($termDate, $recipients, fn () => new RosterChangedMail($termDate, $changes));
    }

    /**
     * @param  array<int, ?SetupGroup>  $groups
     * @param  array<int, ?User>  $drivers
     * @return Collection<int, User>
     */
    private function affectedUsers(array $groups, array $drivers): Collection
    {
        $users = collect($drivers)->filter();

        foreach (array_filter($groups) as $group) {
            $users = $users
                ->merge($group->members)
                ->merge($group->van_drivers);
        }

        return $users
            ->filter(fn ($user) => filled($user->email))
            ->unique('id')
            ->values();
    }
}
