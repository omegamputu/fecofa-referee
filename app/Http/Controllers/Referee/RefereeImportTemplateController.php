<?php

namespace App\Http\Controllers\Referee;

use App\Actions\ImportRefereesFromCsv;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefereeImportTemplateController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            echo "\xEF\xBB\xBF";
            fputcsv($output, ImportRefereesFromCsv::TEMPLATE_HEADERS, ';', '"', '');
            fclose($output);
        }, 'modele-import-arbitres.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
