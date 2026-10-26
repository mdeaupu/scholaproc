<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ProcurementDocument extends Model
{
    protected $fillable = [
        'procurement_request_id',
        'document_type',
        'document_number',
        'document_date',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $document): void {
            if ($document->getOriginal('issued_at') !== null
                && $document->isDirty(['subsidiary_id', 'subsidiary_snapshot', 'issued_at'])) {
                throw new LogicException('Snapshot Anak Perusahaan pada dokumen yang sudah diterbitkan tidak dapat diubah.');
            }

            if ($document->getOriginal('issued_at') === null && $document->issued_at !== null) {
                $request = $document->procurementRequest()->with('subsidiary')->firstOrFail();

                if (! $request->subsidiary) {
                    throw new LogicException('Dokumen resmi tidak dapat diterbitkan sebelum Anak Perusahaan ditetapkan.');
                }

                $document->subsidiary_id = $request->subsidiary_id;
                $document->subsidiary_snapshot = $request->subsidiary->documentIdentitySnapshot();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'subsidiary_snapshot' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function issue(): void
    {
        if (! $this->exists) {
            throw new LogicException('Dokumen harus disimpan sebelum diterbitkan.');
        }

        if ($this->issued_at !== null) {
            throw new LogicException('Dokumen ini sudah diterbitkan.');
        }

        $this->issued_at = now();
        $this->save();
    }
}
