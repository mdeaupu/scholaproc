<?php

namespace App\Observers;

use App\Models\GeneratedDocument;
use Illuminate\Support\Facades\Storage;

class GeneratedDocumentObserver
{
    public function created(GeneratedDocument $document): void
    {
        $tempPath = Storage::disk('local')->path($document->file_path);

        if (file_exists($tempPath)) {
            $document->addMedia($tempPath)
                ->usingName($document->document_type . '-' . $document->procurementRequest->uuid)
                ->usingFileName(basename($tempPath))
                ->toMediaCollection('documents');

            $document->updateQuietly([
                'file_path' => $document->getFirstMedia('documents')->getPath()
            ]);
        }
    }

    public function deleted(GeneratedDocument $document): void
    {

    }
}