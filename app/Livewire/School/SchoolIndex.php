<?php

namespace App\Livewire\School;

use App\Models\School;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class SchoolIndex extends Component
{
    use WithPagination, Toast;

    public bool $confirmingSuspend = false;
    public bool $confirmingDestroy = false;
    public ?int $targetSchoolId = null;
    public string $targetSchoolName = '';
    public string $search = '';
    public string $filterStatus = '';
    public bool $detailModal = false;
    public ?School $selectedSchool = null;

    public function mount(): void
    {
        if (session()->has('toast_success')) {
            $this->success(session('toast_success'));
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Akses ditolak. Hanya Owner yang dapat melihat halaman ini.');
        }

        $schools = School::with(['setting'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('npsn', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->latest()
            ->paginate(10);

        $headers = [
            ['key' => 'npsn', 'label' => 'NPSN', 'class' => 'w-24 font-mono'],
            ['key' => 'name', 'label' => 'Nama Sekolah'],
            ['key' => 'phone_number', 'label' => 'No. Telepon', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-28'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right w-48', 'sortable' => false]
        ];

        return view('livewire.school.school-index', [
            'schools' => $schools,
            'headers' => $headers
        ])->layout('layouts.app');
    }

    public function show(int $id): void
    {
        $this->selectedSchool = School::with(['setting'])->findOrFail($id);
        $this->detailModal = true;
    }

    public function activate(int $id): void
    {
        $school = School::findOrFail($id);
        $school->activate();
        $this->success("Sekolah {$school->name} kini aktif kembali.");
    }

    public function confirmSuspend(int $id, string $name): void
    {
        $this->targetSchoolId = $id;
        $this->targetSchoolName = $name;
        $this->confirmingSuspend = true;
    }

    public function suspend(): void
    {
        if ($this->targetSchoolId) {
            $school = School::findOrFail($this->targetSchoolId);

            $school->suspend();

            $this->warning("Sekolah {$school->name} berhasil dibekukan sementara.");

            $this->confirmingSuspend = false;
            $this->targetSchoolId = null;
            $this->targetSchoolName = '';
        }
    }

    public function confirmDestroy(int $id, string $name): void
    {
        $this->targetSchoolId = $id;
        $this->targetSchoolName = $name;
        $this->confirmingDestroy = true;
    }

    public function destroy(): void
    {
        if ($this->targetSchoolId) {
            $school = School::findOrFail($this->targetSchoolId);
            $school->delete();
            $this->error("Sekolah {$school->name} berhasil dihapus dari sistem.");

            $this->confirmingDestroy = false;
            $this->targetSchoolId = null;
        }
    }
}
