<?php

namespace App\Services;

use App\Mail\VanDriverDatesChangedMail;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Support\Collection;

class VanDriverRota
{
    public function __construct(private EmailDispatcher $dispatcher) {}

    /**
     * Run a change, then send one email to each driver whose rota-inferred dates it moved, listing their next three dates.
     * Dates with an assigned driver are left to TermDateObserver.
     */
    public function notifyingChanges(callable $change): mixed
    {
        if (! config('app.readererer_van_drivers') || ! config('app.readererer_setup_groups')) {
            return $change();
        }

        $before = $this->inferredDriverIds($this->upcoming());
        $result = $change();
        $upcoming = $this->upcoming();
        $after = $this->inferredDriverIds($upcoming);

        $driverIds = collect($before)->keys()->merge(array_keys($after))->unique()
            ->reject(fn (int $dateId) => ($before[$dateId] ?? null) === ($after[$dateId] ?? null))
            ->flatMap(fn (int $dateId) => [$before[$dateId] ?? null, $after[$dateId] ?? null])
            ->filter()
            ->unique();

        $recipients = User::findMany($driverIds)
            ->filter(fn (User $driver) => filled($driver->email))
            ->values();

        if ($recipients->isNotEmpty()) {
            $this->dispatcher->send(null, $recipients, fn (?User $driver = null) => new VanDriverDatesChangedMail(
                $driver,
                $upcoming->where('driver_id', $driver?->id ?? 0)->take(3)->pluck('date'),
            ));
        }

        return $result;
    }

    /** @return Collection<int, array{date: TermDate, driver_id: ?int}> */
    private function upcoming(): Collection
    {
        return TermDate::with(['van_driver', 'setup_group.van_drivers'])
            ->where('start_datetime', '>', now())
            ->orderBy('start_datetime')
            ->get()
            ->map(fn (TermDate $termDate) => ['date' => $termDate, 'driver_id' => $termDate->inferred_van_driver?->id]);
    }

    /** @return array<int, int> driver IDs by date ID, for the dates with no assigned driver */
    private function inferredDriverIds(Collection $upcoming): array
    {
        return $upcoming
            ->filter(fn (array $row) => $row['date']->van_driver_id === null && $row['driver_id'] !== null)
            ->mapWithKeys(fn (array $row) => [$row['date']->id => $row['driver_id']])
            ->all();
    }
}
