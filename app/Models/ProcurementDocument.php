<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class ProcurementDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_request_id',
        'document_type',
        'document_number',
        'document_date',
        'document_snapshot',
        'version',
        'issued_by',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $document): void {
            if ($document->getOriginal('issued_at') !== null
                && $document->isDirty([
                    'subsidiary_id',
                    'subsidiary_snapshot',
                    'document_snapshot',
                    'document_number',
                    'document_date',
                    'issued_at',
                    'issued_by',
                ])) {
                throw new LogicException('Dokumen final yang telah diterbitkan tidak dapat diubah.');
            }

            if ($document->getOriginal('issued_at') === null && $document->issued_at !== null) {
                $request = $document->procurementRequest()->with('subsidiary')->firstOrFail();

                if (! $request->subsidiary
                    || $request->subsidiary_assigned_by === null
                    || $request->subsidiary_assigned_at === null
                    || $document->issued_by === null) {
                    throw new LogicException('Dokumen resmi tidak dapat diterbitkan sebelum Anak Perusahaan ditetapkan.');
                }

                $stage = data_get($document->document_snapshot, 'stage', []);
                $allowed = match ($document->document_type) {
                    'purchase_order' => $request->totals_locked_at !== null
                        && in_array($request->status, ['price_agreed', 'processing'], true),
                    'delivery' => isset($stage['delivery_id'])
                        && $request->deliveries()->whereKey($stage['delivery_id'])->exists(),
                    'bast', 'inspection' => isset($stage['receipt_id'])
                        && $request->receipts()->whereKey($stage['receipt_id'])->whereNotNull('verified_at')->exists(),
                    'invoice' => $request->invoice()->exists()
                        && in_array($request->status, ['invoiced', 'payment_pending'], true),
                    'receipt' => $request->payment()->whereNotNull('confirmed_at')->exists(),
                    'negotiation' => in_array($request->status, ['negotiation', 'price_agreed'], true),
                    'planning' => in_array($request->status, ['verified', 'negotiation', 'price_agreed', 'processing'], true),
                    'cover', 'supplier_declaration' => true,
                    default => false,
                };

                if (! $allowed) {
                    throw new LogicException('Tahap transaksi belum memenuhi syarat penerbitan dokumen ini.');
                }

                $document->subsidiary_id = $request->subsidiary_id;
                $document->subsidiary_snapshot = $request->subsidiary_snapshot
                    ?? $request->subsidiary->documentIdentitySnapshot();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'subsidiary_snapshot' => 'array',
            'document_snapshot' => 'array',
            'issued_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function isComplete(): bool
    {
        return ! empty($this->document_number) && ! empty($this->document_date);
    }

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function generateNumber(string $suffix = ''): string
    {
        $subsidiary = $this->procurementRequest?->subsidiary;
        $slug = $subsidiary ? Str::slug($subsidiary->name) : 'dokumen';
        $type = strtoupper((string) $this->document_type);
        $year = now()->format('Y');

        return trim("{$slug}/{$type}/{$year}/{$suffix}", '/');
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
