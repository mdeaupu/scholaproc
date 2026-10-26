<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementRequest;
use App\Models\User;
use Exception;
use Livewire\Component;
use Mary\Traits\Toast;

class ProcurementReviewPanel extends Component
{
    use Toast;

    public ProcurementRequest $procurementRequest;

    public string $revisionReason = '';

    public string $rejectReason = '';

    public string $cancellationReason = '';

    public bool $revisionModal = false;

    public bool $rejectModal = false;

    public bool $cancelModal = false;

    public function mount(ProcurementRequest $procurementRequest): void
    {
        $this->authorizeInternalStaff();
        $this->procurementRequest = $procurementRequest;
        $this->loadDetails();
    }

    public function verify(): void
    {
        $this->authorizeInternalStaff();

        try {
            $this->procurementRequest->verify(auth()->user());
            $this->refreshAfterTransition();
            $this->success('Pengajuan berhasil diverifikasi.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function requestRevision(): void
    {
        $this->authorizeInternalStaff();
        $this->validate([
            'revisionReason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $this->procurementRequest->requestRevision(auth()->user(), $this->revisionReason);
            $this->refreshAfterTransition();
            $this->revisionModal = false;
            $this->revisionReason = '';
            $this->success('Permintaan revisi berhasil dicatat.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function reject(): void
    {
        $this->authorizeInternalStaff();
        $this->validate([
            'rejectReason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $this->procurementRequest->reject(auth()->user(), $this->rejectReason);
            $this->refreshAfterTransition();
            $this->rejectModal = false;
            $this->rejectReason = '';
            $this->success('Pengajuan ditolak dan alasannya telah dicatat.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function startNegotiation(): void
    {
        $this->authorizeInternalStaff();

        try {
            $this->procurementRequest->startNegotiation(auth()->user());
            $this->refreshAfterTransition();
            $this->success('Pengajuan berhasil diteruskan ke negosiasi.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function cancelRequest(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        if ($user->isSchool()) {
            $this->authorizeSchoolOwner();
        } else {
            $this->authorizeInternalStaff();
        }

        $this->validate([
            'cancellationReason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $this->procurementRequest->cancel($user, $this->cancellationReason);
            $this->refreshAfterTransition();
            $this->cancelModal = false;
            $this->cancellationReason = '';
            $this->success('Pengajuan berhasil dibatalkan.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function render()
    {
        $this->authorizeInternalStaff();
        $this->loadDetails();

        return view('livewire.procurement.procurement-review-panel');
    }

    private function refreshAfterTransition(): void
    {
        $this->loadDetails();
        $this->dispatch('procurement-request-updated');
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
        ]);
    }

    private function authorizeInternalStaff(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isSuperAdmin()), 403);
    }

    private function authorizeSchoolOwner(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->isSchool() && $user->school_id, 403);
        abort_unless($this->procurementRequest->school_id === $user->school_id, 404);
    }
}
