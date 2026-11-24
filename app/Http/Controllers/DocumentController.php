<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;

class DocumentController extends Controller
{
    public function download(GeneratedDocument $document)
    {
        $filePath = $document->downloadUrl();

        $fileName = $document->document_type . '-' . $document->procurementRequest->uuid . '.pdf';

        if (! file_exists($filePath)) {
            abort(404, 'File fisik PDF tidak ditemukan di server.');
        }

        return response()->download($filePath, $fileName);
    }
}
