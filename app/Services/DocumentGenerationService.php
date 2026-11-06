<?php

namespace App\Services;

use App\Helpers\Terbilang;
use App\Models\ProcurementDocument;
use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

class DocumentGenerationService
{
    private const TYPES = [
        'cover',
        'summary',
        'planning',
        'negotiation',
        'purchase_order',
        'delivery',
        'inspection',
        'bast',
        'invoice',
        'receipt',
        'supplier_declaration',
    ];

    public function issue(
        ProcurementRequest $request,
        string $type,
        User $actor,
        array $stageData = []
    ): ProcurementDocument {
        if (! in_array($type, self::TYPES, true)) {
            throw new LogicException('Jenis dokumen tidak dikenal.');
        }

        return DB::transaction(function () use ($request, $type, $actor, $stageData): ProcurementDocument {
            $request = ProcurementRequest::query()
                ->with([
                    'school.setting',
                    'packageCategory',
                    'budgetYear',
                    'fundingSource',
                    'items.unit',
                    'items.negotiations',
                    'subsidiary.legalDocuments',
                    'signatories',
                ])
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->assertStage($request, $type, $stageData);
            $stageData = $this->snapshotStage($request, $type, $stageData);

            $previous = $request->documents()
                ->where('document_type', $type)
                ->orderByDesc('version')
                ->first();
            if ($previous?->issued_at !== null) {
                if (in_array($type, ['bast', 'delivery', 'inspection'], true)) {
                    $stageKey = $type === 'delivery' ? 'delivery_id' : 'receipt_id';
                    $stageId = $stageData[$stageKey] ?? null;
                    if ($stageId !== null) {
                        $existingStageDocument = $request->documents()
                            ->where('document_type', $type)
                            ->whereNotNull('issued_at')
                            ->get()
                            ->first(fn (ProcurementDocument $issued): bool => data_get($issued->document_snapshot, 'stage.'.$stageKey) == $stageId
                            );
                        if ($existingStageDocument) {
                            return $existingStageDocument;
                        }
                    }
                } else {
                    return $previous;
                }
            }

            $version = ((int) ($previous?->version ?? 0)) + 1;
            $identity = $this->completeIdentitySnapshot(
                $request->subsidiary_snapshot ?? [],
                $request->subsidiary?->documentIdentitySnapshot() ?? []
            );
            if (! $identity) {
                throw new LogicException('Snapshot identitas Anak Perusahaan tidak tersedia.');
            }
            $identity = $this->captureLetterheadSnapshot($identity);
            if (in_array($type, ['bast', 'inspection'], true)) {
                $purchaseOrder = $request->documents()
                    ->where('document_type', 'purchase_order')
                    ->whereNotNull('issued_at')
                    ->orderByDesc('version')
                    ->first();

                if (! $purchaseOrder) {
                    throw new LogicException('BAST memerlukan snapshot surat pesanan yang telah diterbitkan.');
                }

                $stageData['purchase_order'] = [
                    'number' => $purchaseOrder->document_number,
                    'date' => $purchaseOrder->document_date?->toDateString(),
                ];
            }

            if ($type === 'bast') {
                $stageData['signatories'] = $request->signatories
                    ->map(fn ($signatory): array => [
                        'role' => $signatory->role,
                        'name' => $signatory->name,
                        'nip' => $signatory->nip,
                        'title' => $signatory->title,
                    ])
                    ->values()
                    ->all();
            }

            $schoolSnapshot = $this->schoolSnapshot($request);
            [$version, $documentNumber] = $this->nextVersionAndNumber($request, $type, $version);
            $documentDate = now()->toDateString();
            $documentDateSnapshot = $this->dateSnapshot($documentDate);
            $snapshot = [
                'document_type' => $type,
                'document_number' => $documentNumber,
                'document_date' => $documentDate,
                'document_date_numeric' => $documentDateSnapshot['numeric'],
                'document_date_words' => $documentDateSnapshot['words'],
                'version' => $version,
                'generated_at' => now()->toIso8601String(),
                'school' => $schoolSnapshot,
                'subsidiary' => $identity,
                'transaction' => $this->transactionSnapshot($request, $type, $identity, $schoolSnapshot),
                'stage' => $stageData,
                'documents' => $this->relatedDocumentSnapshot($request),
            ];
            $document = $request->documents()->create([
                'document_type' => $type,
                'document_number' => $documentNumber,
                'document_date' => $documentDate,
                'version' => $version,
                'issued_by' => $actor->id,
                'document_snapshot' => $snapshot,
                'subsidiary_id' => $request->subsidiary_id,
                'subsidiary_snapshot' => $identity,
            ]);
            $document->issue();

            return $document->refresh();
        }, attempts: 3);
    }

    public function viewFor(ProcurementDocument $document): ?string
    {
        return match ($document->document_type) {
            'cover' => 'documents.cover',
            'summary' => 'documents.generated-summary',
            'planning' => 'documents.planning',
            'negotiation' => 'documents.negotiation',
            'purchase_order' => 'documents.surat-pesanan',
            'delivery' => 'documents.delivery',
            'inspection' => 'documents.inspection',
            'bast' => 'documents.bast',
            'invoice' => 'documents.invoice',
            'receipt' => 'documents.generated-receipt',
            'supplier_declaration' => 'documents.supplier-declaration',
            default => null,
        };
    }

    public function downloadFilename(ProcurementDocument $document): string
    {
        $prefix = match ($document->document_type) {
            'cover' => 'cover',
            'summary' => 'application_summary',
            'planning' => 'planning_document',
            'negotiation' => 'negotiation_document',
            'purchase_order' => 'purchase_order',
            'delivery' => 'delivery_document',
            'inspection' => 'inspection_report',
            'bast' => 'bast',
            'invoice' => 'invoice',
            'receipt' => 'receipt',
            'supplier_declaration' => 'supplier_declaration',
            default => throw new LogicException('Jenis dokumen tidak dikenal.'),
        };

        return "{$prefix}_{$document->id}.pdf";
    }

    /**
     * @return array{snapshot:array}
     */
    public function viewData(ProcurementDocument $document): array
    {
        $snapshot = $document->document_snapshot ?? [];
        $snapshot['document_number'] ??= $document->document_number;
        $snapshot['document_date'] ??= $document->document_date?->toDateString();
        $dateSnapshot = $this->dateSnapshot($snapshot['document_date']);
        $snapshot['document_date_numeric'] ??= $dateSnapshot['numeric'];
        $snapshot['document_date_words'] ??= $dateSnapshot['words'];

        $documentIdentity = $document->subsidiary_snapshot ?? [];
        $snapshotIdentity = data_get($snapshot, 'subsidiary', []);
        $snapshot['subsidiary'] = array_replace_recursive(
            is_array($documentIdentity) ? $documentIdentity : [],
            is_array($snapshotIdentity) ? $snapshotIdentity : []
        );

        $encodedLetterhead = data_get($snapshot, 'subsidiary.letterhead_base64');
        $mimeType = data_get($snapshot, 'subsidiary.letterhead_mime_type');
        if (! is_string($encodedLetterhead) || $encodedLetterhead === '') {
            $path = data_get($snapshot, 'subsidiary.letterhead_path');
            $disk = Storage::disk('public');
            if (is_string($path) && $path !== '' && $disk->exists($path)) {
                $legacyMimeType = $disk->mimeType($path);
                if (in_array($legacyMimeType, ['image/jpeg', 'image/png'], true)) {
                    $encodedLetterhead = base64_encode($disk->get($path));
                    $mimeType = $legacyMimeType;
                    data_set($snapshot, 'subsidiary.letterhead_base64', $encodedLetterhead);
                    data_set($snapshot, 'subsidiary.letterhead_mime_type', $mimeType);
                }
            }
        }

        $decodedLetterhead = is_string($encodedLetterhead)
            ? base64_decode($encodedLetterhead, true)
            : false;

        if (! is_string($encodedLetterhead)
            || ! is_string($decodedLetterhead)
            || $decodedLetterhead === ''
            || ! is_string($mimeType)
            || ! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            throw new LogicException('Snapshot dokumen tidak memiliki kop surat Base64 yang valid.');
        }

        return ['snapshot' => $snapshot];
    }

    /**
     * @return array<string, mixed>
     */
    private function schoolSnapshot(ProcurementRequest $request): array
    {
        $school = $request->school;
        $officials = $request->signatories
            ->map(fn ($signatory): array => [
                'role' => $signatory->role,
                'name' => $signatory->name,
                'nip' => $signatory->nip,
                'title' => $signatory->title,
            ])
            ->keyBy('role');

        if (! $officials->has('headmaster') && $school?->principal_name) {
            $officials->put('headmaster', [
                'role' => 'headmaster',
                'name' => $school->principal_name,
                'nip' => $school->principal_nip,
                'title' => 'Kepala Sekolah',
            ]);
        }

        $headmaster = $officials->get('headmaster');
        $executor = $officials->get('executor') ?? $headmaster;

        return [
            'id' => $school?->id,
            'name' => $school?->name,
            'npsn' => $school?->npsn,
            'address' => $school?->address,
            'postal_code' => $school?->postal_code,
            'phone' => $school?->phone_number,
            'email' => $school?->email,
            'letterhead' => $school?->setting?->getLetterHead() ?? [
                'pusat' => '',
                'provinsi' => '',
                'sub_wilayah' => null,
                'sekolah' => strtoupper((string) $school?->name),
                'alamat_lengkap' => $school?->address,
                'kontak' => 'Telp: '.($school?->phone_number ?? '-').' | Email: '.($school?->email ?? '-'),
            ],
            'officials' => [
                'headmaster' => $headmaster,
                'executor' => $executor,
                'inspector' => $officials->get('inspector'),
                'treasurer' => $officials->get('treasurer'),
                'all' => $officials->values()->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionSnapshot(
        ProcurementRequest $request,
        string $type,
        array $identity,
        array $schoolSnapshot
    ): array {
        $priceLines = collect($request->price_snapshot['items'] ?? [])
            ->keyBy('item_id');
        $items = $request->items->map(function (ProcurementRequestItem $item) use ($priceLines): array {
            $lockedLine = $priceLines->get($item->id, []);
            $offeredPrice = (float) ($lockedLine['estimated_price'] ?? $item->estimated_price);
            $negotiatedPrice = (float) ($lockedLine['official_price'] ?? $item->official_price ?? 0);

            return [
                'item_id' => $item->id,
                'line_number' => $item->line_number,
                'item_name' => $lockedLine['item_name'] ?? $item->item_name,
                'specification' => $lockedLine['specification'] ?? $item->specification,
                'unit' => $lockedLine['unit'] ?? $item->unit?->name,
                'quantity' => (int) ($lockedLine['quantity'] ?? $item->quantity),
                'offered_price' => $offeredPrice,
                'offered_amount' => $offeredPrice * (int) ($lockedLine['quantity'] ?? $item->quantity),
                'negotiated_price' => $negotiatedPrice,
                'negotiated_amount' => $negotiatedPrice * (int) ($lockedLine['quantity'] ?? $item->quantity),
                'estimated_price' => $offeredPrice,
                'estimated_amount' => $offeredPrice * (int) ($lockedLine['quantity'] ?? $item->quantity),
                'official_price' => $negotiatedPrice,
                'amount' => $lockedLine['amount']
                    ?? number_format($negotiatedPrice * (int) ($lockedLine['quantity'] ?? $item->quantity), 2, '.', ''),
                'negotiation_status' => $item->negotiation_status,
                'negotiations' => $item->negotiations
                    ->sortBy('round_number')
                    ->map(fn (ProcurementNegotiation $negotiation): array => [
                        'round_number' => $negotiation->round_number,
                        'offered_by' => $negotiation->offered_by,
                        'offered_price' => (float) $negotiation->offered_price,
                        'status' => $negotiation->status,
                        'notes' => $negotiation->notes,
                        'response_note' => $negotiation->response_note,
                        'school_approved_at' => $negotiation->school_approved_at?->toIso8601String(),
                        'provider_approved_at' => $negotiation->provider_approved_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all(),
            ];
        })->values();

        $tax = $request->tax_snapshot ?? [];
        $taxBreakdown = [
            'ppn' => [
                'rate' => $tax['ppn_rate'] ?? $request->ppn_rate,
                'amount' => $tax['ppn_amount'] ?? ($tax['validated'] ?? false ? $request->totalPpn() : null),
            ],
            'pph_22' => [
                'rate' => $tax['pph_22_rate'] ?? $request->pph_22_rate,
                'amount' => $tax['pph_22_amount'] ?? ($tax['validated'] ?? false ? $request->totalPph22() : null),
            ],
            'pph_23' => [
                'rate' => $tax['pph_23_rate'] ?? $request->pph_23_rate,
                'amount' => $tax['pph_23_amount'] ?? ($tax['validated'] ?? false ? $request->totalPph23() : null),
            ],
            'validated' => (bool) ($tax['validated'] ?? false),
            'basis' => $tax['basis'] ?? null,
        ];
        $payment = $request->payment()->whereNotNull('confirmed_at')->first();
        $paymentConfirmer = $payment?->confirmed_by
            ? User::query()->find($payment->confirmed_by)
            : null;
        $amount = $type === 'receipt'
            ? (float) ($payment?->amount ?? $request->grand_total ?? 0)
            : (float) ($request->grand_total ?? $request->subtotal ?? 0);

        return [
            'uuid' => $request->uuid,
            'status' => $request->status,
            'budget_year' => $request->budgetYear?->name,
            'package_category' => $request->packageCategory?->name,
            'funding_source' => $request->fundingSource?->name,
            'start_date' => $request->start_date?->toDateString(),
            'end_date' => $request->end_date?->toDateString(),
            'target_receipt_date' => $request->end_date?->toDateString(),
            'target_receipt_date_numeric' => $this->dateSnapshot($request->end_date?->toDateString())['numeric'],
            'target_receipt_date_words' => $this->dateSnapshot($request->end_date?->toDateString())['words'],
            'duration' => $request->work_duration_text,
            'work_duration_text' => $request->work_duration_text,
            'items' => $items->all(),
            'item_count' => $items->count(),
            'quantity_total' => $items->sum('quantity'),
            'offer_subtotal' => $items->sum('offered_amount'),
            'negotiated_subtotal' => $items->sum('negotiated_amount'),
            'subtotal' => $request->subtotal,
            'tax' => $tax,
            'tax_breakdown' => $taxBreakdown,
            'ppn_amount' => $taxBreakdown['ppn']['amount'],
            'pph_22_amount' => $taxBreakdown['pph_22']['amount'],
            'pph_23_amount' => $taxBreakdown['pph_23']['amount'],
            'tax_amount' => $request->tax_amount,
            'grand_total' => $request->grand_total,
            'amount' => $amount,
            'amount_in_words' => Terbilang::make($amount, true),
            'payment' => $payment ? [
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'paid_at' => $payment->paid_at?->toDateString(),
                'confirmed_at' => $payment->confirmed_at?->toIso8601String(),
                'confirmed_by' => $payment->confirmed_by,
                'confirmed_by_name' => $paymentConfirmer?->name,
            ] : null,
            'receipt_signatories' => [
                'school' => data_get($schoolSnapshot, 'officials.headmaster.name'),
                'treasurer' => data_get($request->signatories->firstWhere('role', 'treasurer'), 'name'),
                'provider' => $identity['director_name'] ?? null,
                'payment_confirmer' => $paymentConfirmer?->name,
            ],
        ];
    }

    /**
     * @return array<string, array{number: string|null, date: string|null}|null>
     */
    private function relatedDocumentSnapshot(ProcurementRequest $request): array
    {
        $types = ['purchase_order', 'receipt'];
        $documents = [];

        foreach ($types as $type) {
            $document = $request->documents()
                ->where('document_type', $type)
                ->whereNotNull('issued_at')
                ->orderByDesc('version')
                ->first();
            $documents[$type] = $document ? [
                'number' => $document->document_number,
                'date' => $document->document_date?->toDateString(),
            ] : null;
        }

        return $documents;
    }

    /**
     * @param  array<string, mixed>  $stageData
     * @return array<string, mixed>
     */
    private function snapshotStage(ProcurementRequest $request, string $type, array $stageData): array
    {
        if ($type === 'delivery' && isset($stageData['delivery_id'])) {
            $delivery = $request->deliveries()
                ->with('items.requestItem.unit')
                ->findOrFail($stageData['delivery_id']);

            $stageData['delivered_at'] = $delivery->delivered_at?->toDateString();
            $stageData['notes'] = $delivery->notes;
            $stageData['items'] = $delivery->items->map(fn ($item): array => [
                'item' => $item->requestItem?->item_name,
                'specification' => $item->requestItem?->specification,
                'unit' => $item->requestItem?->unit?->name,
                'quantity' => $item->quantity,
            ])->all();
        }

        if (in_array($type, ['bast', 'inspection'], true) && isset($stageData['receipt_id'])) {
            $receipt = $request->receipts()
                ->with('items.requestItem.unit')
                ->findOrFail($stageData['receipt_id']);

            $stageData['received_at'] = $receipt->received_at?->toDateString();
            $stageData['verified_at'] = $receipt->verified_at?->toIso8601String();
            $stageData['inspection_date'] = $receipt->verified_at?->toDateString();
            $inspectionDateSnapshot = $this->dateSnapshot($stageData['inspection_date']);
            $stageData['inspection_date_numeric'] = $inspectionDateSnapshot['numeric'];
            $stageData['inspection_date_words'] = $inspectionDateSnapshot['words'];
            $stageData['receipt_notes'] = $receipt->notes;
            $stageData['items'] = $receipt->items->map(fn ($item): array => [
                'item' => $item->requestItem?->item_name,
                'specification' => $item->requestItem?->specification,
                'unit' => $item->requestItem?->unit?->name,
                'ordered_quantity' => $item->requestItem?->quantity,
                'quantity' => $item->quantity,
                'condition' => $item->condition,
                'issue_notes' => $item->issue_notes,
            ])->all();
        }

        return $stageData;
    }

    private function assertStage(ProcurementRequest $request, string $type, array $stageData): void
    {
        if (! $request->hasSubsidiary()) {
            throw new LogicException('Dokumen resmi memerlukan Anak Perusahaan yang ditetapkan.');
        }

        $allowed = match ($type) {
            'purchase_order' => $request->isTotalsLocked()
                && in_array($request->status, [
                    ProcurementRequest::STATUS_PRICE_AGREED,
                    ProcurementRequest::STATUS_PROCESSING,
                ], true),
            'summary' => in_array($request->status, [
                ProcurementRequest::STATUS_VERIFIED,
                ProcurementRequest::STATUS_NEGOTIATION,
                ProcurementRequest::STATUS_PRICE_AGREED,
                ProcurementRequest::STATUS_PROCESSING,
                ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                ProcurementRequest::STATUS_DISPUTED,
                ProcurementRequest::STATUS_RECEIVED,
                ProcurementRequest::STATUS_INVOICED,
                ProcurementRequest::STATUS_PAYMENT_PENDING,
                ProcurementRequest::STATUS_COMPLETED,
            ], true),
            'delivery' => in_array($request->status, [
                ProcurementRequest::STATUS_PROCESSING,
                ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                ProcurementRequest::STATUS_DISPUTED,
            ], true)
                && isset($stageData['delivery_id']),
            'negotiation' => in_array($request->status, [
                ProcurementRequest::STATUS_PRICE_AGREED,
                ProcurementRequest::STATUS_PROCESSING,
                ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                ProcurementRequest::STATUS_DISPUTED,
                ProcurementRequest::STATUS_RECEIVED,
                ProcurementRequest::STATUS_INVOICED,
                ProcurementRequest::STATUS_PAYMENT_PENDING,
                ProcurementRequest::STATUS_COMPLETED,
            ], true),
            'planning' => in_array($request->status, [
                ProcurementRequest::STATUS_VERIFIED,
                ProcurementRequest::STATUS_NEGOTIATION,
                ProcurementRequest::STATUS_PRICE_AGREED,
                ProcurementRequest::STATUS_PROCESSING,
            ], true),
            'bast', 'inspection' => isset($stageData['receipt_id'])
                && $request->receipts()->whereKey($stageData['receipt_id'])->whereNotNull('verified_at')->exists(),
            'invoice' => in_array($request->status, [
                ProcurementRequest::STATUS_INVOICED,
                ProcurementRequest::STATUS_PAYMENT_PENDING,
            ], true)
                && $request->invoice()->exists()
                && $request->allQuantitiesReceived()
                && ! $request->hasOpenReceiptFollowUps()
                && $request->isTotalsLocked()
                && $request->isTaxConfigurationValidated()
                && $request->hasIssuedDocument('bast'),
            'receipt' => $request->payment()->whereNotNull('confirmed_at')->exists()
                && $request->payment_confirmed_at !== null,
            default => true,
        };

        if (! $allowed) {
            throw new LogicException("Dokumen {$type} belum memenuhi tahap penerbitan yang dipersyaratkan.");
        }

    }

    private function captureLetterheadSnapshot(array $identity): array
    {
        $path = $identity['letterhead_path'] ?? null;
        $disk = Storage::disk('public');

        if (! is_string($identity['name'] ?? null)
            || trim($identity['name']) === ''
            || ! is_string($identity['npwp'] ?? null)
            || trim($identity['npwp']) === ''
            || ! is_string($identity['director_name'] ?? null)
            || trim($identity['director_name']) === '') {
            throw new LogicException('Snapshot Anak Perusahaan tidak memiliki nama, NPWP, dan direktur yang lengkap.');
        }

        if (! is_string($path) || $path === '' || ! $disk->exists($path)) {
            throw new LogicException(
                'Berkas kop surat tidak tersedia pada snapshot Anak Perusahaan; unggah dan tetapkan ulang identitas sebelum menerbitkan dokumen.'
            );
        }

        if (! in_array($disk->mimeType($path), ['image/jpeg', 'image/png'], true)) {
            throw new LogicException('Berkas kop surat pada snapshot bukan PNG atau JPEG yang didukung.');
        }

        $contents = $disk->get($path);
        if ($contents === '') {
            throw new LogicException('Berkas kop surat pada snapshot kosong.');
        }

        $identity['letterhead_base64'] = base64_encode($contents);
        $identity['letterhead_mime_type'] = $disk->mimeType($path);

        return $identity;
    }

    /**
     * Keep captured identity values, while backfilling fields absent from older
     * transaction snapshots before the first document is issued.
     *
     * @param  array<string, mixed>  $captured
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private function completeIdentitySnapshot(array $captured, array $current): array
    {
        foreach ($current as $key => $value) {
            if (! array_key_exists($key, $captured)
                || $captured[$key] === null
                || $captured[$key] === ''
                || ($key === 'legal_documents' && $captured[$key] === [])) {
                $captured[$key] = $value;
            }
        }

        return $captured;
    }

    /**
     * The request row is locked by issue() while this number is allocated. The
     * unique database index is the final guard against collisions across requests.
     *
     * @return array{int, string}
     */
    private function nextVersionAndNumber(ProcurementRequest $request, string $type, int $version): array
    {
        do {
            $number = $this->number($request, $type, $version);
            $numberExists = ProcurementDocument::query()
                ->where('document_number', $number)
                ->exists();

            if ($numberExists) {
                $version++;
            }
        } while ($numberExists);

        return [$version, $number];
    }

    private function number(ProcurementRequest $request, string $type, int $version): string
    {
        $code = strtoupper(match ($type) {
            'purchase_order' => 'SP',
            'delivery' => 'KIRIM',
            'bast' => 'BAST',
            'inspection' => 'PEMERIKSAAN',
            'invoice' => 'INV',
            'receipt' => 'KW',
            default => str_replace('_', '-', $type),
        });
        $year = now()->format('Y');
        $versionSuffix = $version > 1 ? '-V'.$version : '';

        return "{$code}/{$request->id}/{$year}{$versionSuffix}";
    }

    /**
     * @return array{numeric:string, words:string}
     */
    private function dateSnapshot(?string $date): array
    {
        if (! $date) {
            return ['numeric' => '-', 'words' => '-'];
        }

        $parsedDate = Carbon::parse($date)->locale('id');
        $month = $parsedDate->translatedFormat('F');

        return [
            'numeric' => $parsedDate->format('d-m-Y'),
            'words' => mb_strtolower(
                Terbilang::make((int) $parsedDate->format('d')).' '.$month.' '.
                Terbilang::make((int) $parsedDate->format('Y'))
            ),
        ];
    }
}
