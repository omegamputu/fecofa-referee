<?php

namespace App\Http\Controllers\Referee;

use App\Http\Controllers\Controller;
use App\Models\Instructors\Instructor;
use Illuminate\Http\Request;
use App\Models\Referees\Referee;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    //
    public function refereeExportPdf(Request $request)
    {
        // On reprend la même logique de filtre que ta liste Livewire
        $query = Referee::query()
            ->with(['league', 'refereeRole']);

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('person_id', 'like', "%{$search}%");
            });
        }

        if ($leagueId = $request->input('league')) {
            $query->where('league_id', $leagueId);
        }

        if ($roleId = $request->input('role')) {
            $query->where('referee_role_id', $roleId);
        }

        $referees = $query
            ->orderBy('id', 'asc')
            //->orderBy('first_name')
            ->get();
        
        $generatedAt = now();
        
            $pdf = Pdf::loadView('exports.referees_pdf_list', [
                'referees' => $referees,
                'generatedAt' => $generatedAt,
            ])
            ->setPaper('a4', 'portrait'); // ou 'landscape'

        return $pdf->download('fecofa_referees_list_' . $generatedAt->format('Ymd_His') . '.pdf');
    }

    public function instructorExportPdf(Request $request)
    {
        // On reprend la même logique de filtre que ta liste Livewire
        $query = Instructor::query()
            ->with(['instructorRole','refereeCategory', 'refereeRole']);

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%");
            });
        }

        if ($instructorRoleId = $request->input('role')) {
            $query->where('instructor_role_id', $instructorRoleId);
        }

        if ($refereeRoleId = $request->input('role')) {
            $query->where('referee_role_id', $refereeRoleId);
        }

        $instructors = $query
            ->orderBy('id', 'asc')
            ->get();
        
        $generatedAt = now();
        
            $pdf = Pdf::loadView('exports.instructors_pdf_list', [
                'instructors' => $instructors,
                'generatedAt' => $generatedAt,
            ])
            ->setPaper('a4', 'portrait'); // ou 'landscape'

        return $pdf->download('fecofa_instructors_list_' . $generatedAt->format('Ymd_His') . '.pdf');
    }
}
