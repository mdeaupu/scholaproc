<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementPayment;
use App\Models\ProcurementReceipt;
use App\Models\ProcurementReceiptItem;
use App\Models\ProcurementRequest;
use App\Models\ProcurementSignatory;
use App\Models\User;
use App\Services\DocumentGenerationService;
use App\Services\ProcurementProcessingService;
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

    public array $signatoriesForm = [
        'headmaster' => ['name' => '', 'nip' => '', 'title' => 'Kepala Sekolah'],
        'inspector' => ['name' => '', 'nip' => '', 'title' => 'Pemeriksa Barang'],
        'treasurer' => ['name' => '', 'nip' => '', 'title' => 'Bendahara Sekolah'],
    ];

    public function mount(ProcurementRequest $procurementRequest): void
    {
        $this->authorizeInternal();
        $this->procurementRequest = $procurementRequest;
        $this->deliveryDate = now()->toDateString();
        $this->receiptDate = now()->toDateString();
        $this->paymentDate = now()->toDateString();
        $this->loadDetails();
        $this->fillSignatoriesForm();
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

    public function issueReceipt(DocumentGenerationService $documents): void
    {
        $this->perform(fn () => $documents->issue($this->procurementRequest, 'receipt', auth()->user()));
    }

    public function saveSignatories(): void
    {
        $this->authorizeInternal();
        $this->validate([
            'signatoriesForm.headmaster.name' => ['required', 'string', 'max:255'],
            'signatoriesForm.headmaster.nip' => ['nullable', 'string', 'max:50'],
            'signatoriesForm.headmaster.title' => ['required', 'string', 'max:100'],
            'signatoriesForm.inspector.name' => ['required', 'string', 'max:255'],
            'signatoriesForm.inspector.nip' => ['nullable', 'string', 'max:50'],
            'signatoriesForm.inspector.title' => ['required', 'string', 'max:100'],
            'signatoriesForm.treasurer.name' => ['required', 'string', 'max:255'],
            'signatoriesForm.treasurer.nip' => ['nullable', 'string', 'max:50'],
            'signatoriesForm.treasurer.title' => ['required', 'string', 'max:100'],
        ], [
            'signatoriesForm.headmaster.name.required' => 'Nama Kepala Sekolah wajib diisi.',
            'signatoriesForm.inspector.name.required' => 'Nama Pemeriksa wajib diisi.',
            'signatoriesForm.treasurer.name.required' => 'Nama Bendahara wajib diisi.',
        ]);

        $this->perform(function (): void {
            foreach (['headmaster', 'inspector', 'treasurer'] as $role) {
                $row = $this->signatoriesForm[$role];
                $this->procurementRequest->signatories()->updateOrCreate(
                    ['role' => $role],
                    [
                        'name' => trim((string) $row['name']),
                        'nip' => trim((string) ($row['nip'] ?? '')) !== '' ? trim((string) $row['nip']) : null,
                        'title' => trim((string) $row['title']),
                    ]
                );
            }
        });
        $this->fillSignatoriesForm();
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
            'openIssues' => $this->procurementRequest->receipts
                ->flatMap(fn (ProcurementReceipt $receipt) => $receipt->items)
                ->where('follow_up_status', ProcurementReceiptItem::FOLLOW_UP_OPEN),
        ]);
    }

    private function loadDetails(): void
    {
        $this->procurementRequest->load([
            'school',
            'signatories',
            'items.unit',
            'items.deliveryItems',
            'items.receiptItems.receipt',
            'deliveries.items.requestItem',
            'receipts.recordedBy',
            'receipts.items.requestItem',
            'invoice.payment',
            'documents',
        ]);
    }

    private function fillSignatoriesForm(): void
    {
        $existing = $this->procurementRequest->signatories->keyBy('role');
        $school = $this->procurementRequest->school;

        $previousSignatories = ProcurementSignatory::query()
            ->whereHas('procurementRequest', fn ($query) => $query
                ->where('school_id', $this->procurementRequest->school_id)
                ->whereKeyNot($this->procurementRequest->id))
            ->orderByDesc('id')
            ->get()
            ->unique('role')
            ->keyBy('role');

        $this->signatoriesForm = [
            'headmaster' => [
                'name' => (string) ($existing->get('headmaster')?->name
                    ?? $previousSignatories->get('headmaster')?->name
                    ?? $school?->principal_name
                    ?? ''),
                'nip' => (string) ($existing->get('headmaster')?->nip
                    ?? $previousSignatories->get('headmaster')?->nip
                    ?? $school?->principal_nip
                    ?? ''),
                'title' => (string) ($existing->get('headmaster')?->title
                    ?? $previousSignatories->get('headmaster')?->title
                    ?? 'Kepala Sekolah'),
            ],
            'inspector' => [
                'name' => (string) ($existing->get('inspector')?->name
                    ?? $previousSignatories->get('inspector')?->name
                    ?? ''),
                'nip' => (string) ($existing->get('inspector')?->nip
                    ?? $previousSignatories->get('inspector')?->nip
                    ?? ''),
                'title' => (string) ($existing->get('inspector')?->title
                    ?? $previousSignatories->get('inspector')?->title
                    ?? 'Pemeriksa Barang'),
            ],
            'treasurer' => [
                'name' => (string) ($existing->get('treasurer')?->name
                    ?? $previousSignatories->get('treasurer')?->name
                    ?? ''),
                'nip' => (string) ($existing->get('treasurer')?->nip
                    ?? $previousSignatories->get('treasurer')?->nip
                    ?? ''),
                'title' => (string) ($existing->get('treasurer')?->title
                    ?? $previousSignatories->get('treasurer')?->title
                    ?? 'Bendahara Sekolah'),
            ],
        ];
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
