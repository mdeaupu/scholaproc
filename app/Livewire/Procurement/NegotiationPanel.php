<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use Exception;
use Livewire\Component;
use Mary\Traits\Toast;

class NegotiationPanel extends Component
{
    use Toast;

    public ProcurementRequest $procurementRequest;

    public ?int $selectedItemId = null;
    public $items = [];

    public float $offerPrice = 0;
    public string $offerNotes = '';

    public bool $showRejectModal = false;
    public ?int $rejectingNegotiationId = null;
    public string $rejectReason = '';

    public bool $showDetailModal = false;
    public ?ProcurementNegotiation $detailNegotiation = null;

    public function mount(ProcurementRequest $procurementRequest): void
    {
        $this->procurementRequest = $procurementRequest;
        $this->loadItems();

        if ($this->items->isNotEmpty() && !$this->selectedItemId) {
            $this->selectedItemId = $this->items->first()->id;
        }
    }

    public function loadItems(): void
    {
        $this->procurementRequest->load('items.unit', 'items.negotiations');
        $this->items = $this->procurementRequest->items;
    }

    public function selectItem(int $itemId): void
    {
        $this->selectedItemId = $itemId;
        $this->resetOfferFields();
    }

    private function getSelectedItem(): ?ProcurementRequestItem
    {
        if (!$this->selectedItemId) {
            return null;
        }

        return $this->items->firstWhere('id', $this->selectedItemId);
    }

    private function getNegotiations(): \Illuminate\Support\Collection
    {
        if (!$this->selectedItemId) {
            return collect();
        }

        return ProcurementNegotiation::where('procurement_request_item_id', $this->selectedItemId)
            ->with('user')
            ->orderBy('round_number')
            ->get();
    }

    public function offer(): void
    {
        $user = auth()->user();

        $this->validate([
            'offerPrice' => 'required|numeric|min:1',
        ], [
            'offerPrice.required' => 'Harga penawaran wajib diisi.',
            'offerPrice.numeric' => 'Harga harus berupa angka.',
            'offerPrice.min' => 'Harga minimal Rp 1.',
        ]);

        $item = ProcurementRequestItem::findOrFail($this->selectedItemId);

        $offeredBy = $user->isSchool()
            ? ProcurementNegotiation::OFFERED_BY_SCHOOL
            : ProcurementNegotiation::OFFERED_BY_ADMIN;

        try {
            ProcurementNegotiation::offer(
                $item,
                $user,
                $offeredBy,
                $this->offerPrice,
                $this->offerNotes ?: null
            );

            $this->success('Tawaran harga berhasil diajukan.');
            $this->resetOfferFields();
            $this->loadItems();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function accept(int $negotiationId): void
    {
        $user = auth()->user();

        if (!$user->canDecideNegotiation($this->procurementRequest)) {
            $this->error('Anda tidak memiliki hak akses untuk menerima tawaran.');
            return;
        }

        $negotiation = ProcurementNegotiation::findOrFail($negotiationId);

        try {
            $negotiation->accept($user);
            $this->success('Tawaran harga diterima. Harga resmi berhasil ditetapkan.');
            $this->loadItems();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function openRejectModal(int $negotiationId): void
    {
        $user = auth()->user();

        if (!$user->canDecideNegotiation($this->procurementRequest)) {
            $this->error('Anda tidak memiliki hak akses untuk menolak tawaran.');
            return;
        }

        $this->rejectingNegotiationId = $negotiationId;
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function confirmReject(): void
    {
        $user = auth()->user();

        $this->validate([
            'rejectReason' => 'required|string|min:3',
        ], [
            'rejectReason.required' => 'Alasan penolakan wajib diisi.',
            'rejectReason.min' => 'Alasan minimal 3 karakter.',
        ]);

        $negotiation = ProcurementNegotiation::findOrFail($this->rejectingNegotiationId);

        try {
            $negotiation->reject($user, $this->rejectReason);
            $this->success('Tawaran harga ditolak.');
            $this->showRejectModal = false;
            $this->rejectingNegotiationId = null;
            $this->rejectReason = '';
            $this->loadItems();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function showDetail(int $negotiationId): void
    {
        $this->detailNegotiation = ProcurementNegotiation::with('user')->findOrFail($negotiationId);
        $this->showDetailModal = true;
    }

    public function render()
    {
        return view('livewire.procurement.negotiation-panel', [
            'selectedItem' => $this->getSelectedItem(),
            'negotiations' => $this->getNegotiations(),
        ])->layout('layouts.app');
    }

    private function resetOfferFields(): void
    {
        $this->offerPrice = 0;
        $this->offerNotes = '';
    }
}
