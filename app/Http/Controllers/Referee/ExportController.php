<?php

namespace App\Http\Controllers\Referee;

use App\Http\Controllers\Controller;
use App\Models\Instructors\Instructor;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
            // ->orderBy('first_name')
            ->get();

        $generatedAt = now();

        $pdf = Pdf::loadView('exports.referees_pdf_list', [
            'referees' => $referees,
            'generatedAt' => $generatedAt,
        ])
            ->setPaper('a4', 'portrait'); // ou 'landscape'

        return $pdf->download('fecofa_referees_list_'.$generatedAt->format('Ymd_His').'.pdf');
    }

    public function instructorExportPdf(Request $request)
    {
        // On reprend la même logique de filtre que ta liste Livewire
        $query = Instructor::query()
            ->with(['instructorRole', 'refereeCategory', 'refereeRole']);

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%");
            });
        }

        if ($instructorRoleId = $request->input('instructor_role')) {
            $query->where('instructor_role_id', $instructorRoleId);
        }

        if ($refereeRoleId = $request->input('referee_role')) {
            $query->where('referee_role_id', $refereeRoleId);
        }

        if ($categoryId = $request->input('category')) {
            $query->where('referee_category_id', $categoryId);
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

        return $pdf->download('fecofa_instructors_list_'.$generatedAt->format('Ymd_His').'.pdf');
    }

    public function eligibleRefereesExportPdf(Request $request)
    {
        $validated = $request->validate([
            'season' => ['required', 'integer', 'between:2000,2100'],
        ]);
        $seasonYear = (int) $validated['season'];

        $referees = Referee::query()
            ->eligibleForSeason($seasonYear)
            ->with([
                'league:id,code,name',
                'refereeCategory:id,name',
                'refereeRole:id,name,slug',
                'medicalExams' => fn ($query) => $query->where('season_year', $seasonYear),
                'physicalTests' => fn ($query) => $query->where('season_year', $seasonYear),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $centralCount = $referees->where('refereeRole.slug', RefereeRole::CENTRAL_SLUG)->count();
        $assistantCount = $referees->where('refereeRole.slug', RefereeRole::ASSISTANT_SLUG)->count();
        $generatedAt = now();

        return Pdf::loadView('exports.eligible_referees_pdf_list', [
            'referees' => $referees,
            'seasonYear' => $seasonYear,
            'centralCount' => $centralCount,
            'assistantCount' => $assistantCount,
            'generatedAt' => $generatedAt,
        ])
            ->setPaper('a4', 'landscape')
            ->download('fecofa_arbitres_eligibles_'.$seasonYear.'-'.($seasonYear + 1).'.pdf');
    }
}
