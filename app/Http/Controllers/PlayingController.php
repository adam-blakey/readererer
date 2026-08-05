<?php

namespace App\Http\Controllers;

use App\Models\TermDate;
use Illuminate\Support\Facades\Auth;

class PlayingController extends Controller
{
    /**
     * The apostrophe is a typographic one on purpose: a page name passes
     * through two Blade component hops (view -> layout -> page header) and
     * each one HTML-escapes a plain "'" again, so it would end up on the page
     * as "&#039;".
     */
    private const PAGE_NAME = 'Who you’re playing with';

    /**
     * The upcoming rehearsals and concerts the signed-in user is playing at,
     * each with a summary of who else is coming.
     */
    public function index()
    {
        $user = Auth::user();

        $term_dates = TermDate::upcoming()
            ->forUser($user)
            ->with(['concert_ensemble', 'setup_group'])
            ->limit(10)
            ->get();

        $totals = $term_dates->mapWithKeys(fn (TermDate $term_date) => [
            $term_date->id => member_status_totals($term_date->players(), $term_date),
        ]);

        return view('playing.index', [
            'term_dates' => $term_dates,
            'totals' => $totals,
            'page_name' => self::PAGE_NAME,
            'page_subname' => 'Upcoming rehearsals and concerts',
        ]);
    }

    /**
     * Who is playing at a single rehearsal or concert.
     */
    public function show(TermDate $termDate)
    {
        $this->authorize('viewPlayers', $termDate);

        $players = $termDate->players();

        return view('playing.show', [
            'term_date' => $termDate,
            'ensemble' => $termDate->concert_ensemble,
            'groups' => members_by_attendance($players, $termDate),
            'totals' => member_status_totals($players, $termDate),
            'page_name' => self::PAGE_NAME,
            'page_subname' => $termDate->name,
        ]);
    }
}
