<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Models\TermDate;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class SeatingPlanPdfController extends Controller
{
    public function download(Ensemble $ensemble, TermDate $termDate)
    {
        $this->authorize('view', $ensemble);

        $members = User::latest()
            ->with('attendances')
            ->with('ensembles')
            ->with('setup_group')
            ->get()
            ->filter(function($user) use ($ensemble) { return $user->ensembles->contains($ensemble) && $user->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id != null; })
            ->values();

        Pdf::setOption(['debugCss' => true]);
        $pdf = Pdf::loadView('mail.seating-plan-pdf', compact('ensemble', 'termDate', 'members'))->setPaper('a4', 'landscape');
        return $pdf->stream();

        //return view('mail.seating-plan-pdf', compact('ensemble', 'termDate', 'members'));
    }
}
