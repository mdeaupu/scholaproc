<?php

namespace App\Livewire\School;

use App\Models\School;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class SchoolIndex extends Component
{
    use Toast, WithPagination;

    private const SORTABLE_COLUMNS = ['name', 'npsn', 'address', 'status', 'created_at'];

    public bool $confirmingDestroy = false;

    public ?int $targetSchoolId = null;

    public string $targetSchoolName = '';

    public string $search = '';

    public string $filterStatus = '';

    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $detailModal = false;

    public ?School $selectedSchool = null;

    protected function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Akses ditolak. Hanya Superadmin yang dapat mengelola sekolah.');
    }

    public function mount(): void
    {
        $this->authorizeSuperAdmin();

        if (session()->has('toast_success')) {
            $this->success(session('toast_success'));
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorizeSuperAdmin();

        $sortColumn = in_array($this->sortBy['column'] ?? null, self::SORTABLE_COLUMNS, true)
            ? $this->sortBy['column']
            : 'created_at';
        $sortDirection = ($this->sortBy['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        $schools = School::with(['setting', 'account'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('npsn', 'like', '%'.$this->search.'%')
                        ->orWhere('address', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        $firstItem = $schools->firstItem();
        if ($firstItem !== null) {
            $schools->getCollection()->each(function (School $school, int $index) use ($firstItem) {
                $school->setAttribute('row_no', $firstItem + $index);
            });
        }

        $totalSchools = School::count();
        $activeSchools = School::where('status', 'active')->count();
        $suspendedSchools = School::where('status', 'suspended')->count();

        $percentage = fn (int $count): float => $totalSchools > 0 ? round($count * 100 / $totalSchools, 1) : 0.0;

        $headers = [
            ['key' => 'no', 'label' => 'No.', 'class' => 'w-12 text-center hidden md:table-cell', 'sortable' => false],
            ['key' => 'name', 'label' => 'Nama Sekolah'],
            ['key' => 'npsn', 'label' => 'NPSN', 'class' => 'w-28 hidden md:table-cell'],
            ['key' => 'address', 'label' => 'Alamat', 'class' => 'hidden md:table-cell'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-24'],
            ['key' => 'created_at', 'label' => 'Tanggal Bergabung', 'class' => 'w-36 hidden xl:table-cell'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'w-14 text-right', 'sortable' => false],
        ];

        return view('livewire.school.school-index', [
            'schools' => $schools,
            'headers' => $headers,
            'totalSchools' => $totalSchools,
            'activeSchools' => $activeSchools,
            'suspendedSchools' => $suspendedSchools,
            'percentActive' => $percentage($activeSchools),
            'percentSuspended' => $percentage($suspendedSchools),
        ])->layout('layouts.app');
    }

    public function show(int $id): void
    {
        $this->authorizeSuperAdmin();

        $this->selectedSchool = School::with(['setting', 'account', 'fundingSources'])->findOrFail($id);
        $this->detailModal = true;
    }

    public function confirmDestroy(int $id, string $name): void
    {
        $this->authorizeSuperAdmin();

        $this->targetSchoolId = $id;
        $this->targetSchoolName = $name;
        $this->confirmingDestroy = true;
    }

    public function destroy(): void
    {
        $this->authorizeSuperAdmin();

        if ($this->targetSchoolId) {
            $school = School::with('account')->findOrFail($this->targetSchoolId);

            DB::transaction(function () use ($school) {
                $school->delete();

                if ($school->account) {
                    $school->account->update([
                        'status' => 'suspended',
                    ]);
                }
            });

            $this->error("Sekolah {$school->name} berhasil dihapus dari sistem.");

            $this->confirmingDestroy = false;
            $this->targetSchoolId = null;
        }
    }
}
