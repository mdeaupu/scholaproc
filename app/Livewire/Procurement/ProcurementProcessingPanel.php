<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementPayment;
use App\Models\ProcurementReceipt;
use App\Models\ProcurementReceiptItem;
use App\Models\ProcurementRequest;
use App\Services\ProcurementProcessingService;
use App\Models\User;
use Exception;
use Livewire\Component;
use Mary\Traits\Toast;

class ProcurementProcessingPanel extends Component
{
    use Toast;

    public ProcurementRequest $procurementRequest;

    public string $deliveryDate = '';

    public string $receiptDate = '';

    public array $deliveryQuantities = [];

    public array $receiptRows = [];

    public string $deliveryNotes = '';

    public string $receiptNotes = '';

    public string $resolutionNotes = '';

    public string $paymentAmount = '';

    public string $paymentMethod = '';

    public string $paymentReference = '';

    public string $paymentDate = '';

    public function mount(ProcurementRequest $procurementRequest): void
    {
        $this->authorizeInternal();
        $this->procurementRequest = $procurementRequest;
        $this->deliveryDate = now()->toDateString();
        $this->receiptDate = now()->toDateString();
        $this->paymentDate = now()->toDateString();
        $this->loadDetails();
    }

    public function startProcessing(ProcurementProcessingService $service): void
    {
        $this->perform(fn () => $service->startProcessing($this->procurementRequest, auth()->user()));
    }

    public function recordDelivery(ProcurementProcessingService $service): void
    {
        $this->authorizeInternal();
        $this->validate([
            'deliveryDate' => ['required', 'date'],
            'deliveryQuantities' => ['required', 'array'],
            'deliveryQuantities.*' => ['nullable', 'integer', 'min:0'],
            'deliveryNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $quantities = array_filter(
            array_map('intval', $this->deliveryQuantities),
            fn (int $quantity): bool => $quantity > 0
        );

        $this->perform(fn () => $service->recordDelivery(
            $this->procurementRequest,
            auth()->user(),
            $this->deliveryDate,
            $quantities,
            $this->deliveryNotes
        ));
        $this->deliveryNotes = '';
        $this->deliveryQuantities = [];
    }

    public function recordReceipt(ProcurementProcessingService $service): void
    {
        $this->authorizeInternal();
        $this->validate([
            'receiptDate' => ['required', 'date'],
            'receiptRows' => ['required', 'array'],
            'receiptRows.*.quantity' => ['nullable', 'integer', 'min:0'],
            'receiptRows.*.condition' => ['required', 'in:good,incomplete,damaged,non_conforming'],
            'receiptRows.*.issue_notes' => ['nullable', 'string', 'max:2000'],
            'receiptNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $rows = array_filter(
            $this->receiptRows,
            fn (array $row): bool => (int) ($row['quantity'] ?? 0) > 0
        );
        $this->perform(fn () => $service->recordReceipt(
            $this->procurementRequest,
            auth()->user(),
            $this->receiptDate,
            $rows,
            $this->receiptNotes
        ));
        $this->receiptRows = [];
        $this->receiptNotes = '';
    }

    public function verifyReceipt(int $receiptId, ProcurementProcessingService $service): void
    {
        $this->authorizeInternal();
        $receipt = $this->procurementRequest->receipts()->findOrFail($receiptId);
        $this->perform(fn () => $service->verifyReceipt($receipt, auth()->user()));
    }

    public function resolveIssue(int $itemId, ProcurementProcessingService $service): void
    {
        $this->authorizeInternal();
        $this->validate(['resolutionNotes' => ['required', 'string', 'min:5', 'max:2000']]);

        $item = ProcurementReceiptItem::query()
            ->whereHas('receipt', fn ($query) => $query->where('procurement_request_id', $this->procurementRequest->id))
            ->findOrFail($itemId);
        $this->perform(fn () => $service->resolveReceiptIssue($item, auth()->user(), $this->resolutionNotes));
        $this->resolutionNotes = '';
    }

    public function processInvoice(ProcurementProcessingService $service): void
    {
        $this->perform(fn () => $service->processInvoice($this->procurementRequest, auth()->user()));
    }

    public function recordPayment(ProcurementProcessingService $service): void
    {
        $this->authorizeInternal();
        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'gt:0'],
            'paymentMethod' => ['required', 'string', 'max:50'],
            'paymentReference' => ['nullable', 'string', 'max:100'],
            'paymentDate' => ['required', 'date'],
        ]);

        $invoice = $this->procurementRequest->invoice()->firstOrFail();
        $this->perform(fn () => $service->recordPayment(
            $invoice,
            auth()->user(),
            (float) $this->paymentAmount,
            $this->paymentMethod,
            $this->paymentReference,
            $this->paymentDate
        ));
        $this->paymentAmount = '';
        $this->paymentMethod = '';
        $this->paymentReference = '';
    }

    public function confirmPayment(ProcurementProcessingService $service): void
    {
        $payment = $this->procurementRequest->invoice?->payment;
        abort_unless($payment instanceof ProcurementPayment, 404);
        $this->perform(fn () => $service->confirmPayment($payment, auth()->user()));
    }

    public function complete(): void
    {
        $this->perform(fn () => $this->procurementRequest->complete(auth()->user()));
    }

    public function render()
    {
        $this->authorizeInternal();
        $this->loadDetails();

        return view('livewire.procurement.procurement-processing-panel', [
            'receivedQuantities' => $this->receivedQuantities(),
            'openIssues' => $this->procurementRequest->receipts
                ->flatMap(fn (ProcurementReceipt $receipt) => $receipt->items)
                ->where('follow_up_status', ProcurementReceiptItem::FOLLOW_UP_OPEN),
        ]);
    }

    private function receivedQuantities(): array
    {
        return $this->procurementRequest->items->mapWithKeys(fn ($item): array => [
            $item->id => $item->receiptItems
                ->filter(fn (ProcurementReceiptItem $line): bool => $line->receipt?->verified_at !== null)
                ->sum('quantity'),
        ])->all();
    }

    private function loadDetails(): void
    {
        $this->procurementRequest->load([
            'items.unit',
            'items.deliveryItems',
            'items.receiptItems.receipt',
            'deliveries.items.requestItem',
            'receipts.items.requestItem',
            'invoice.payment',
            'documents',
        ]);
    }

    private function perform(callable $action): void
    {
        $this->authorizeInternal();

        try {
            $action();
            $this->loadDetails();
            $this->dispatch('procurement-request-updated');
            $this->success('Perubahan pemrosesan berhasil dicatat.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    private function authorizeInternal(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isSuperAdmin()), 403);
    }
}
