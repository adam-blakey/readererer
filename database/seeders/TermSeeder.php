<?php

namespace Database\Seeders;

use App\Models\Ensemble;
use App\Models\Setlist;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use App\Models\Term;
use App\Models\TermDate;
use Illuminate\Support\Carbon;

class TermSeeder extends Seeder
{
    protected $faker;
    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $setlist = Setlist::all()->random(1)->first();

        $term = Term::factory(3)->create();

        $term->each(function ($term) use ($setlist) {
            [$season, $year] = explode(' ', $term->name);

            switch ($season) {
                case 'Spring':
                    $month_start = 1;
                    $month_end = 4;
                    break;
                case 'Summer':
                    $month_start = 5;
                    $month_end = 7;
                    break;
                case 'Autumn':
                    $month_start = 9;
                    $month_end = 12;
                    break;
                default:
                    throw new \Exception("Season $season not detected");
            }

            $firstSunday = Carbon::createFromDate($year, $month_start, 1);
            while ($firstSunday->dayOfWeek != CarbonInterface::SUNDAY) {
                $firstSunday->addDay();
            }
            $firstSunday->hour(0);
            $firstSunday->minute(0);
            $firstSunday->second(0);

            for ($d = $firstSunday->copy(); $d->month <= $month_end && $d->year == $year; $d->addWeek()) {
                $term->term_dates()->create([
                    'start_datetime' => $d->copy()->addHours(9.5),
                    'end_datetime' => $d->copy()->addHours(12.5),
                    'concert_ensemble_id' => null,
                    'setlist_id' => $setlist->id,
                ]);
            }

            Ensemble::all()->each(function ($ensemble) use ($term, $year, $month_start, $month_end, $setlist) {
                $concert_date = Carbon::parse($this->faker->dateTimeBetween(Carbon::createFromDate($year, $month_start, 1), Carbon::createFromDate($year, $month_end, 28)));
                $concert_date->hour(19);
                $concert_date->minute(30);
                $concert_date->second(0);

                $term->term_dates()->create([
                    'start_datetime' => $concert_date,
                    'end_datetime' => $concert_date->copy()->addHours(2),
                    'concert_ensemble_id' => $ensemble->id,
                    'setlist_id' => $setlist->id,
                ]);
            });
        });
    }
}
