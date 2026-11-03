<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementRequest;
use App\Models\Subsidiary;
use App\Models\User;
use Exception;
use Livewire\Component;
use Mary\Traits\Toast;

class ProcurementProcessPanel extends Component
{
    use Toast;

    public ProcurementRequest $procurementRequest;

    public string $activeTab = 'informasi';

    public string $rejectReason = '';

    public string $subsidiaryId = '';

    public string $taxEvaluationMessage = '';

    public bool $taxEvaluationResolved = false;

    public bool $rejectModal = false;

    public bool $subsidiaryModal = false;

    public function mount(ProcurementRequest $procurementRequest): void
    {
        $this->authorizeRequest($procurementRequest);
        $this->procurementRequest = $procurementRequest;
        $this->loadDetails();
        $this->taxEvaluationMessage = $procurementRequest->tax_configuration_basis
            ?? 'Konfigurasi pajak belum dievaluasi.';
        $this->taxEvaluationResolved = $procurementRequest->isTaxConfigurationValidated();

        if ($procurementRequest->status === ProcurementRequest::STATUS_NEGOTIATION) {
            $this->activeTab = 'negosiasi';
        }
    }

    public function submitRequest(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->isSchool(), 403);

        $this->perform(function () use ($user): void {
            $this->procurementRequest->submit($user);
        }, 'Pengajuan berhasil dikirim untuk diverifikasi.');
    }

    public function verify(): void
    {
        $user = $this->authorizeInternal();

        $this->perform(function () use ($user): void {
            $this->procurementRequest->verify($user);
        }, 'Pengajuan berhasil diverifikasi.');
    }

    public function reject(): void
    {
        $user = $this->authorizeInternal();
        $this->validate([
            'rejectReason' => ['required', 'string', 'min:5'],
        ], [
            'rejectReason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $this->perform(function () use ($user): void {
            $this->procurementRequest->reject($user, $this->rejectReason);
        }, 'Pengajuan telah ditolak.');

        $this->rejectModal = false;
        $this->rejectReason = '';
    }

    public function assignSubsidiary(): void
    {
        $user = $this->authorizeInternal();
        $this->validate([
            'subsidiaryId' => ['required', 'integer'],
        ]);

        $subsidiary = Subsidiary::query()
            ->where('status', Subsidiary::STATUS_ACTIVE)
            ->findOrFail($this->subsidiaryId);

        $this->perform(function () use ($subsidiary, $user): void {
            $this->procurementRequest->assignSubsidiary($subsidiary, $user);
        }, 'Anak Perusahaan berhasil ditetapkan.');

        $this->subsidiaryModal = false;
        $this->subsidiaryId = '';
    }

    public function evaluateTaxes(): void
    {
        $user = $this->authorizeInternal();

        try {
            $evaluation = $this->procurementRequest->evaluateTaxConfiguration($user);
            $this->loadDetails();
            $this->taxEvaluationResolved = $evaluation['status'] === 'RESOLVED';
            $this->taxEvaluationMessage = $evaluation['basis'];
            $this->dispatch('procurement-request-updated');

            if ($this->taxEvaluationResolved) {
                $this->success('Evaluasi pajak tervalidasi berhasil diterapkan.');
            } else {
                $this->warning('Aturan pajak belum dapat diterapkan; periksa dasar evaluasinya.');
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function startNegotiation(): void
    {
        $user = $this->authorizeInternal();

        $this->perform(function () use ($user): void {
            $this->procurementRequest->startNegotiation($user);
        }, 'Negosiasi harga dimulai.');

        $this->activeTab = 'negosiasi';
    }

    public function setActiveTab(string $tab): void
    {
        abort_unless(in_array($tab, ['informasi', 'negosiasi', 'pengiriman', 'dokumen'], true), 404);

        $this->activeTab = $tab;
    }

    public function complete(): void
    {
        $user = $this->authorizeInternal();

        $this->perform(function () use ($user): void {
            $this->procurementRequest->complete($user);
        }, 'Pengadaan berhasil diselesaikan.');
    }

    public function render()
    {
        $this->authorizeRequest($this->procurementRequest);
        $this->loadDetails();

        return view('livewire.procurement.procurement-process-panel', [
            'histories' => $this->procurementRequest->histories
                ->sortByDesc('created_at')
                ->values(),
            'negotiationProgress' => $this->procurementRequest->negotiationProgress(),
            'subsidiaries' => Subsidiary::query()
                ->where('status', Subsidiary::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app');
    }

    private function loadDetails(): void
    {
        $this->procurementRequest->load([
            'items.unit',
            'items.negotiations.user',
            'histories.createdBy',
            'school',
            'subsidiary',
            'deliveries.items.requestItem',
            'receipts.items.requestItem',
            'documents',
        ]);
    }

    private function perform(callable $action, string $successMessage): void
    {
        try {
            $action();
            $this->procurementRequest->refresh();
            $this->loadDetails();
            $this->dispatch('procurement-request-updated');
            $this->success($successMessage);
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    private function authorizeInternal(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isSuperAdmin()), 403);

        return $user;
    }

    private function authorizeRequest(ProcurementRequest $request): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);
        abort_unless(
            $user->isAdmin() || $user->isSuperAdmin() || ($user->isSchool() && $user->school_id === $request->school_id),
            404
        );
    }
}
