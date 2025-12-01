<?php

namespace App\Http\Controllers\Referee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Referees\Referee;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    //
    public function pdf(Request $request)
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
        
            $pdf = Pdf::loadView('exports.pdf_list', [
                'referees' => $referees,
                'generatedAt' => $generatedAt,
            ])
            ->setPaper('a4', 'portrait'); // ou 'landscape'

        return $pdf->download('fecofa_referees_list_' . $generatedAt->format('Ymd_His') . '.pdf');
    }
}
