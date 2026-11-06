<?php

namespace App\Http\Controllers;

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Services\DocumentGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ProcurementDocumentController extends Controller
{
    public function issue(
        ProcurementRequest $procurementRequest,
        string $type,
        DocumentGenerationService $documents
    ): RedirectResponse {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin(), 403);

        $documents->issue($procurementRequest, $type, auth()->user());

        return back()->with('toast_success', 'Dokumen berhasil diterbitkan.');
    }

    public function download(
        ProcurementRequest $procurementRequest,
        ProcurementDocument $document,
        DocumentGenerationService $documents
    ): Response {
        abort_unless($document->procurement_request_id === $procurementRequest->id, 404);
        abort_unless($document->issued_at !== null, 404);

        $user = auth()->user();
        abort_unless(
            $user->isAdmin()
            || $user->isSuperAdmin()
            || ($user->isSchool() && $user->school_id === $procurementRequest->school_id),
            403
        );

        $view = $documents->viewFor($document);
        abort_if($view === null, 404, 'Template PDF untuk jenis dokumen ini belum tersedia.');
        $pdf = Pdf::loadView($view, $documents->viewData($document));

        return $pdf->download($documents->downloadFilename($document));
    }
}
