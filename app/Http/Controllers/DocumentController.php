<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use App\Models\ProcurementRequest;
use Exception;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    public function generate(ProcurementRequest $procurement, $type)
    {
        if ($type !== 'all') {
            $method = 'generate' . str($type)->camel()->ucfirst();

            if (!method_exists($procurement, $method)) {
                abort(404, 'Tipe dokumen tidak valid.');
            }
        }

        try {
            if ($type === 'all') {
                $procurement->generateAllDocuments();

                return back()->with('success', 'Semua dokumen berhasil di-generate.');
            }

            $document = $procurement->$method();
            $document->refresh();

            return $this->download($document);
        } catch (Exception $e) {
            Log::error('Gagal generate dokumen: ' . $e->getMessage(), [
                'procurement_id' => $procurement->id,
                'type' => $type,
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function download(GeneratedDocument $document)
    {
        $filePath = $document->downloadUrl();

        $fileName = $document->document_type . '-' . $document->procurementRequest->uuid . '.pdf';

        if (!file_exists($filePath)) {
            abort(404, 'File fisik PDF tidak ditemukan di server.');
        }

        return response()->download($filePath, $fileName);
    }
    public function regenerate(GeneratedDocument $document)
    {
        try {
            $document->regeneratePdf();

            return back()->with('success', 'Dokumen berhasil diperbarui. Silakan klik tombol Download.');
        } catch (Exception $e) {
            Log::error('Gagal regenerate dokumen: ' . $e->getMessage(), [
                'document_id' => $document->id,
            ]);

            return back()->with('error', $e->getMessage());
        }
    }
}