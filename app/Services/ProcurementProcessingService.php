<?php

namespace App\Services;

use App\Models\ProcurementDelivery;
use App\Models\ProcurementInvoice;
use App\Models\ProcurementPayment;
use App\Models\ProcurementReceipt;
use App\Models\ProcurementReceiptItem;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class ProcurementProcessingService
{
    public function __construct(
        private readonly DocumentGenerationService $documents
    ) {}

    public function startProcessing(ProcurementRequest $request, User $actor): void
    {
        $this->authorizeInternal($actor);

        DB::transaction(function () use ($request, $actor): void {
            $request = ProcurementRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== ProcurementRequest::STATUS_PRICE_AGREED
                || ! $request->isTotalsLocked()
                || ! $request->hasSubsidiary()) {
                throw new LogicException('Pesanan hanya dapat diproses setelah harga final dan snapshot Anak Perusahaan tersedia.');
            }

            $request->update(['status' => ProcurementRequest::STATUS_PROCESSING]);
            $request->recordHistory($actor, ProcurementRequest::STATUS_PROCESSING, 'Pemrosesan pesanan dimulai.');
            $this->documents->issue($request, 'purchase_order', $actor);
            activity()->performedOn($request)->causedBy($actor)->log('Pemrosesan pengadaan dimulai');
        });
    }

    /**
     * @param array<int, int> $quantities request item ID => shipped quantity
     */
    public function recordDelivery(
        ProcurementRequest $request,
        User $actor,
        string $date,
        array $quantities,
        ?string $notes = null
    ): ProcurementDelivery {
        $this->authorizeInternal($actor);

        return DB::transaction(function () use ($request, $actor, $date, $quantities, $notes): ProcurementDelivery {
            $request = ProcurementRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! in_array($request->status, [
                ProcurementRequest::STATUS_PROCESSING,
                ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                ProcurementRequest::STATUS_DISPUTED,
            ], true)) {
                throw new LogicException('Pengiriman hanya dapat dicatat untuk pesanan yang sedang diproses.');
            }

            if ($quantities === []) {
                throw ValidationException::withMessages(['quantities' => 'Isi jumlah minimal satu barang yang dikirim.']);
            }

            $delivery = $request->deliveries()->create([
                'delivered_at' => $date,
                'recorded_by' => $actor->id,
                'notes' => $notes,
            ]);

            foreach ($quantities as $itemId => $quantity) {
                $item = $request->items()->lockForUpdate()->find($itemId);
                if (! $item || $quantity < 1) {
                    throw ValidationException::withMessages(['quantities' => 'Barang atau jumlah pengiriman tidak valid.']);
                }

                $alreadyShipped = $item->deliveryItems()->sum('quantity');
                if ($alreadyShipped + $quantity > $item->quantity) {
                    throw ValidationException::withMessages(['quantities' => "Jumlah terkirim {$item->item_name} melebihi jumlah pesanan."]);
                }

                $delivery->items()->create([
                    'procurement_request_item_id' => $item->id,
                    'quantity' => $quantity,
                ]);
            }

            $this->documents->issue($request, 'delivery', $actor, [
                'delivery_id' => $delivery->id,
                'delivered_at' => $delivery->delivered_at?->toDateString(),
                'items' => $delivery->items()->with('requestItem')->get()->map(fn ($line): array => [
                    'item' => $line->requestItem?->item_name,
                    'quantity' => $line->quantity,
                ])->all(),
            ]);
            $request->recordHistory($actor, $request->status, 'Pengiriman dicatat: '.$date);
            activity()->performedOn($request)->causedBy($actor)->withProperties(['delivery_id' => $delivery->id])->log('Pengiriman barang dicatat');

            return $delivery->load('items');
        });
    }

    /**
     * @param array<int, array{quantity:int,condition:string,issue_notes?:?string}> $items
     */
    public function recordReceipt(
        ProcurementRequest $request,
        User $actor,
        string $date,
        array $items,
        ?string $notes = null
    ): ProcurementReceipt {
        $this->authorizeInternal($actor);

        return DB::transaction(function () use ($request, $actor, $date, $items, $notes): ProcurementReceipt {
            $request = ProcurementRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! in_array($request->status, [
                ProcurementRequest::STATUS_PROCESSING,
                ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                ProcurementRequest::STATUS_DISPUTED,
            ], true)) {
                throw new LogicException('Penerimaan hanya dapat dicatat setelah pesanan mulai diproses.');
            }

            if ($items === []) {
                throw ValidationException::withMessages(['items' => 'Isi jumlah minimal satu barang yang diterima.']);
            }

            $receipt = $request->receipts()->create([
                'received_at' => $date,
                'recorded_by' => $actor->id,
                'notes' => $notes,
            ]);

            foreach ($items as $itemId => $values) {
                $item = $request->items()->lockForUpdate()->find($itemId);
                $quantity = (int) ($values['quantity'] ?? 0);
                $condition = $values['condition'] ?? '';
                $issueNotes = trim((string) ($values['issue_notes'] ?? ''));

                if (! $item || $quantity < 1 || ! in_array($condition, ['good', 'incomplete', 'damaged', 'non_conforming'], true)) {
                    throw ValidationException::withMessages(['items' => 'Rincian penerimaan barang tidak valid.']);
                }

                $shipped = (int) $item->deliveryItems()->sum('quantity');
                $received = (int) $item->receiptItems()
                    ->whereHas('receipt', fn ($query) => $query->whereNotNull('verified_at'))
                    ->sum('quantity');
                if ($received + $quantity > $shipped || $received + $quantity > $item->quantity) {
                    throw ValidationException::withMessages(['items' => "Jumlah diterima {$item->item_name} melebihi jumlah terkirim atau pesanan."]);
                }

                $receipt->items()->create([
                    'procurement_request_item_id' => $item->id,
                    'quantity' => $quantity,
                    'condition' => $condition,
                    'issue_notes' => $issueNotes !== '' ? $issueNotes : null,
                    'follow_up_status' => $condition === 'good' ? 'none' : 'open',
                ]);
            }

            $request->recordHistory($actor, $request->status, 'Penerimaan barang dicatat sebagai draf; belum diverifikasi.');
            activity()->performedOn($request)->causedBy($actor)->withProperties(['receipt_id' => $receipt->id])->log('Penerimaan barang dicatat (draft)');

            return $receipt->load('items');
        });
    }

    public function verifyReceipt(ProcurementReceipt $receipt, User $actor): void
    {
        $this->authorizeInternal($actor);

        DB::transaction(function () use ($receipt, $actor): void {
            $receipt = ProcurementReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
            $request = ProcurementRequest::query()->lockForUpdate()->findOrFail($receipt->procurement_request_id);

            if ($receipt->verified_at !== null || $receipt->items()->doesntExist()) {
                throw new LogicException('Penerimaan kosong atau telah diverifikasi.');
            }

            foreach ($receipt->items as $receiptItem) {
                $item = ProcurementRequestItem::query()->lockForUpdate()->findOrFail($receiptItem->procurement_request_item_id);
                $priorReceived = $item->receiptItems()
                    ->where('procurement_receipt_id', '!=', $receipt->id)
                    ->whereHas('receipt', fn ($query) => $query->whereNotNull('verified_at'))
                    ->sum('quantity');

                if ($priorReceived + $receiptItem->quantity > $item->quantity) {
                    throw new LogicException('Jumlah penerimaan terverifikasi melampaui jumlah pesanan.');
                }
            }

            $receipt->update(['verified_by' => $actor->id, 'verified_at' => now()]);
            $hasOpenIssue = $request->hasOpenReceiptFollowUps();
            $fullyReceived = $request->allQuantitiesReceived();
            $newStatus = $hasOpenIssue
                ? ProcurementRequest::STATUS_DISPUTED
                : ($fullyReceived ? ProcurementRequest::STATUS_RECEIVED : ProcurementRequest::STATUS_PARTIALLY_RECEIVED);

            $request->update([
                'status' => $newStatus,
                'verified_at' => $fullyReceived && ! $hasOpenIssue ? now() : null,
                'verified_by' => $fullyReceived && ! $hasOpenIssue ? $actor->id : null,
            ]);
            $request->recordHistory($actor, $newStatus, 'Penerimaan diverifikasi.');
            activity()->performedOn($request)->causedBy($actor)->withProperties(['receipt_id' => $receipt->id])->log('Penerimaan barang diverifikasi');
            $this->issueReceiptDocuments($request, $receipt, $actor);
        });
    }

    public function resolveReceiptIssue(ProcurementReceiptItem $item, User $actor, string $resolution): void
    {
        $this->authorizeInternal($actor);
        $resolution = trim($resolution);
        if (mb_strlen($resolution) < 5) {
            throw new LogicException('Catatan penyelesaian tindak lanjut wajib diisi.');
        }

        DB::transaction(function () use ($item, $actor, $resolution): void {
            $item = ProcurementReceiptItem::query()->lockForUpdate()->findOrFail($item->id);
            if ($item->follow_up_status !== ProcurementReceiptItem::FOLLOW_UP_OPEN) {
                throw new LogicException('Tindak lanjut penerimaan tidak sedang terbuka.');
            }

            $item->update([
                'follow_up_status' => ProcurementReceiptItem::FOLLOW_UP_RESOLVED,
                'issue_notes' => trim(($item->issue_notes ?? '')."\nPenyelesaian: ".$resolution),
            ]);

            $request = $item->receipt->procurementRequest;
            if (! $request->hasOpenReceiptFollowUps() && $request->allQuantitiesReceived()) {
                $request->update([
                    'status' => ProcurementRequest::STATUS_RECEIVED,
                    'verified_at' => now(),
                    'verified_by' => $actor->id,
                ]);
                $this->issueReceiptDocuments($request, $item->receipt, $actor);
            }
            $request->recordHistory($actor, $request->status, 'Tindak lanjut penerimaan diselesaikan: '.$resolution);
            activity()->performedOn($request)->causedBy($actor)->withProperties(['receipt_item_id' => $item->id])->log('Tindak lanjut penerimaan diselesaikan');
        });
    }

    public function processInvoice(ProcurementRequest $request, User $actor): ProcurementInvoice
    {
        $this->authorizeInternal($actor);

        return DB::transaction(function () use ($request, $actor): ProcurementInvoice {
            $request = ProcurementRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== ProcurementRequest::STATUS_RECEIVED
                || ! $request->allQuantitiesReceived()
                || $request->hasOpenReceiptFollowUps()
                || ! $request->isTaxConfigurationValidated()
                || ! $request->isTotalsLocked()
                || ! $request->hasIssuedDocument('bast')) {
                throw new LogicException('Invoice memerlukan penerimaan penuh yang diverifikasi, tindak lanjut selesai, snapshot harga, konfigurasi pajak tervalidasi, dan dokumen penerimaan.');
            }

            if ($request->invoice()->exists()) {
                throw new LogicException('Invoice transaksi ini sudah diproses.');
            }

            $invoice = $request->invoice()->create([
                'invoice_number' => 'INV-'.$request->id.'-'.now()->format('YmdHis'),
                'invoice_date' => now()->toDateString(),
                'subtotal' => $request->subtotal,
                'tax_amount' => $request->tax_amount,
                'total_amount' => $request->grand_total,
                'tax_snapshot' => $request->tax_snapshot,
                'price_snapshot' => $request->price_snapshot,
                'subsidiary_snapshot' => $request->subsidiary_snapshot,
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ]);

            $request->update(['status' => ProcurementRequest::STATUS_INVOICED]);
            $request->recordHistory($actor, ProcurementRequest::STATUS_INVOICED, 'Invoice diproses setelah penerimaan dan administrasi terverifikasi.');
            $this->documents->issue($request, 'invoice', $actor, [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date->toDateString(),
                'subtotal' => $invoice->subtotal,
                'tax_amount' => $invoice->tax_amount,
                'total_amount' => $invoice->total_amount,
                'tax_snapshot' => $invoice->tax_snapshot,
            ]);
            activity()->performedOn($request)->causedBy($actor)->withProperties(['invoice_id' => $invoice->id])->log('Invoice diproses');

            return $invoice;
        });
    }

    public function recordPayment(
        ProcurementInvoice $invoice,
        User $actor,
        float $amount,
        string $method,
        ?string $reference,
        string $paidAt
    ): ProcurementPayment {
        $this->authorizeInternal($actor);
        if ($amount <= 0 || trim($method) === '') {
            throw new LogicException('Jumlah dan metode pembayaran wajib diisi.');
        }

        return DB::transaction(function () use ($invoice, $actor, $amount, $method, $reference, $paidAt): ProcurementPayment {
            $invoice = ProcurementInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $request = ProcurementRequest::query()->lockForUpdate()->findOrFail($invoice->procurement_request_id);
            if ($request->status !== ProcurementRequest::STATUS_INVOICED) {
                throw new LogicException('Pembayaran hanya dapat dicatat setelah invoice diproses.');
            }

            if (abs($amount - (float) $invoice->total_amount) > 0.009) {
                throw new LogicException('Jumlah pembayaran harus sama dengan total invoice; pembayaran sebagian belum dikonfigurasi.');
            }

            if ($invoice->payment()->exists()) {
                throw new LogicException('Pembayaran untuk invoice ini sudah dicatat.');
            }

            $payment = $invoice->payment()->create([
                'amount' => $amount,
                'method' => trim($method),
                'reference' => $reference,
                'paid_at' => $paidAt,
            ]);
            $request->update(['status' => ProcurementRequest::STATUS_PAYMENT_PENDING]);
            $request->recordHistory($actor, ProcurementRequest::STATUS_PAYMENT_PENDING, 'Pembayaran dicatat dan menunggu konfirmasi.');
            activity()->performedOn($request)->causedBy($actor)->withProperties(['payment_id' => $payment->id])->log('Pembayaran dicatat');

            return $payment;
        });
    }

    public function confirmPayment(ProcurementPayment $payment, User $actor): void
    {
        $this->authorizeInternal($actor);

        DB::transaction(function () use ($payment, $actor): void {
            $payment = ProcurementPayment::query()->lockForUpdate()->findOrFail($payment->id);
            $invoice = $payment->invoice;
            $request = ProcurementRequest::query()->lockForUpdate()->findOrFail($invoice->procurement_request_id);

            if ($payment->confirmed_at !== null
                || $request->status !== ProcurementRequest::STATUS_PAYMENT_PENDING
                || (float) $payment->amount !== (float) $invoice->total_amount) {
                throw new LogicException('Pembayaran tidak dapat dikonfirmasi atau nilainya tidak cocok dengan invoice.');
            }

            $payment->update(['confirmed_by' => $actor->id, 'confirmed_at' => now()]);
            $request->update([
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => $actor->id,
            ]);
            $request->recordHistory($actor, $request->status, 'Pembayaran invoice dikonfirmasi.');
            activity()->performedOn($request)->causedBy($actor)->withProperties(['payment_id' => $payment->id])->log('Pembayaran invoice dikonfirmasi');
        });
    }

    private function issueReceiptDocuments(
        ProcurementRequest $request,
        ProcurementReceipt $receipt,
        User $actor
    ): void {
        $stage = ['receipt_id' => $receipt->id];

        $this->documents->issue($request, 'inspection', $actor, $stage);
        $this->documents->issue($request, 'bast', $actor, $stage);
    }

    private function authorizeInternal(User $actor): void
    {
        if (! $actor->isAdmin() && ! $actor->isSuperAdmin()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Hanya Admin Pusat yang dapat memproses transaksi.');
        }
    }
}
