<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Exports\GuestsExport;
use App\Exports\GuestTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\GuestImport;
use App\Models\Invitation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The file downloads around guest import (25.2, 25.5, 25.6).
 *
 * Downloads, not JSON, so they live on the dashboard surface rather than the
 * API: the browser navigates to these, and a Vue island's fetch would only
 * have to hand the bytes back to the browser anyway.
 */
final class GuestImportController extends Controller
{
    /**
     * The blank template (25.2). XLSX by default, CSV on request: a client on
     * Google Sheets would rather have the CSV, and both read back the same.
     */
    public function template(Invitation $invitation, string $format = 'xlsx'): BinaryFileResponse
    {
        Gate::authorize('view', $invitation);

        $xlsx = $format !== 'csv';

        return Excel::download(
            new GuestTemplateExport,
            $xlsx ? 'templat-tamu.xlsx' : 'templat-tamu.csv',
            $xlsx ? ExcelFormat::XLSX : ExcelFormat::CSV,
        );
    }

    /**
     * The guest list as a spreadsheet (M5.9).
     */
    public function export(Invitation $invitation): BinaryFileResponse
    {
        Gate::authorize('view', $invitation);

        return Excel::download(
            new GuestsExport($invitation, route('invitation.show', ['slug' => $invitation->slug])),
            'tamu-'.$invitation->slug.'.xlsx',
            ExcelFormat::XLSX,
        );
    }

    /**
     * The error report (25.5): row number, name as typed, and why it failed.
     *
     * CSV rather than XLSX — it is a list a client reads next to their own
     * spreadsheet, and a CSV opens in whatever they already have open.
     */
    public function errors(GuestImport $import): Response
    {
        Gate::authorize('view', $import);

        $rows = [['baris', 'nama', 'masalah']];

        foreach ($import->errors ?? [] as $error) {
            $rows[] = [(string) $error['row'], $error['name'], $error['message']];
        }

        $handle = fopen('php://temp', 'r+');

        // A BOM, because Excel on Windows reads a plain UTF-8 CSV as Latin-1
        // and turns every Indonesian name with an accent into mojibake.
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="kesalahan-impor-'.$import->getKey().'.csv"',
        ]);
    }
}
