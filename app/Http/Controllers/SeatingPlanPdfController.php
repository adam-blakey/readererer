<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Models\TermDate;
use Barryvdh\DomPDF\Facade\Pdf;

class SeatingPlanPdfController extends Controller
{
    public function show(Ensemble $ensemble, TermDate $termDate)
    {
        $pdf = Pdf::loadView('mail.seating-plan-pdf', compact('ensemble', 'termDate'));
        return $pdf->stream();
    }
}
