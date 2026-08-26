<?php

namespace App\Observers;

use App\Models\ProcurementVerificationFile;

class ProcurementVerificationFileObserver
{
    public function created(ProcurementVerificationFile $file): void
    {
        \Log::info('Verification file uploaded', [
            'procurement_request_id' => $file->procurement_request_id,
            'file_type' => $file->file_type,
            'file_path' => $file->file_path,
            'uploaded_by' => $file->uploaded_by,
        ]);
    }

    public function deleted(ProcurementVerificationFile $file): void
    {
        \Log::info('Verification file deleted', [
            'procurement_request_id' => $file->procurement_request_id,
            'file_type' => $file->file_type,
        ]);
    }
}
