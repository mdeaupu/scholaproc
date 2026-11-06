<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\SubmitProcurementRequest as SubmitProcurementRequestAction;
use App\Models\ProcurementRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class ProcurementRequestDetail extends Component
{
    use Toast;

    public ProcurementRequest $procurementRequest;

    public bool $confirmingDeleteDraft = false;

    public string $activeTab = 'informasi';

    public function mount(ProcurementRequest $procurementRequest): void
    {
        $this->authorizeSchoolScope($procurementRequest);
        $this->procurementRequest = $procurementRequest;
        if ($procurementRequest->status === ProcurementRequest::STATUS_NEGOTIATION) {
            $this->activeTab = 'negosiasi';
        }
        $this->loadDetails();

        if (session()->has('toast_success')) {
            $this->success(
                title: 'Berhasil!',
                description: session('toast_success'),
                position: 'toast-top toast-end',
                icon: 'o-check-circle',
                css: 'alert-success'
            );
        }
    }

    public function submitRequest(SubmitProcurementRequestAction $submitRequest): void
    {
        $this->authorizeSchoolOwner();

        try {
            $this->procurementRequest = $submitRequest->handle($this->procurementRequest, auth()->user());
            $this->loadDetails();
            $this->success('Pengajuan berhasil dikirim untuk diverifikasi.');
        } catch (ValidationException $exception) {
            $this->error($exception->validator->errors()->first());
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function confirmDeleteDraft(): void
    {
        $this->authorizeSchoolOwner();
        abort_unless($this->procurementRequest->status === ProcurementRequest::STATUS_DRAFT, 403);

        $this->confirmingDeleteDraft = true;
    }

    public function setActiveTab(string $tab): void
    {
        abort_unless(in_array($tab, ['informasi', 'negosiasi', 'pengiriman', 'dokumen'], true), 404);

        $this->activeTab = $tab;
    }

    public function deleteDraft()
    {
        $this->authorizeSchoolOwner();
        abort_unless($this->procurementRequest->status === ProcurementRequest::STATUS_DRAFT, 403);

        DB::transaction(function (): void {
            $this->procurementRequest->items->each(fn ($item) => $item->delete());
            $this->procurementRequest->delete();
        });

        $this->confirmingDeleteDraft = false;
        session()->flash('toast_success', 'Draft pengajuan berhasil dihapus.');

        return $this->redirectRoute('procurement.index', navigate: true);
    }

    public function render()
    {
        $this->authorizeSchoolScope($this->procurementRequest);
        $this->loadDetails();

        return view('livewire.procurement.procurement-request-detail')
            ->layout('layouts.app');
    }

    #[On('procurement-request-updated')]
    public function refreshDetails(): void
    {
        $this->authorizeSchoolScope($this->procurementRequest);
        $this->procurementRequest->refresh();
        $this->loadDetails();

        if ($this->procurementRequest->status === ProcurementRequest::STATUS_NEGOTIATION) {
            $this->activeTab = 'negosiasi';
        }
    }

    private function loadDetails(): void
    {
        $this->procurementRequest->load([
            'school.account',
            'packageCategory',
            'fundingSource',
            'budgetYear',
            'items.unit',
            'histories.createdBy',
            'deliveries.items.requestItem',
            'receipts.items.requestItem',
            'documents',
        ]);
    }

    private function authorizeSchoolScope(ProcurementRequest $request): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);
        abort_unless(! $user->isSchool() || $request->school_id === $user->school_id, 404);
    }

    private function authorizeSchoolOwner(): void
    {
        $user = auth()->user();
        abort_unless($user?->isSchool() && $user->school_id, 403);
        abort_unless($this->procurementRequest->school_id === $user->school_id, 404);
    }
}
