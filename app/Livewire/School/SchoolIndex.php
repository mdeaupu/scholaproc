<?php

namespace App\Livewire\School;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class SchoolIndex extends Component
{
    use Toast, WithPagination;

    private const SORTABLE_COLUMNS = ['name', 'npsn', 'address', 'status', 'created_at'];

    public bool $confirmingSuspend = false;

    public bool $confirmingDestroy = false;

    public bool $confirmingResetPassword = false;

    public bool $confirmingToggleAccount = false;

    public ?int $targetSchoolId = null;

    public string $targetSchoolName = '';

    public ?int $targetAccountId = null;

    public string $targetAccountName = '';

    public string $newPassword = '';

    public string $search = '';

    public string $filterStatus = '';

    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $detailModal = false;

    public string $detailTab = 'pengguna';

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
        $totalAccounts = User::where('role', 'school')->count();
        $activeAccounts = User::where('role', 'school')->where('status', 'active')->count();

        $percentage = fn (int $count): float => $totalSchools > 0 ? round($count * 100 / $totalSchools, 1) : 0.0;

        $headers = [
            ['key' => 'no', 'label' => 'No.', 'class' => 'w-12 text-center hidden md:table-cell', 'sortable' => false],
            ['key' => 'name', 'label' => 'Nama Sekolah'],
            ['key' => 'npsn', 'label' => 'NPSN', 'class' => 'w-28 hidden md:table-cell'],
            ['key' => 'address', 'label' => 'Alamat', 'class' => 'hidden md:table-cell'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-24'],
            ['key' => 'akun', 'label' => 'Akun Sekolah', 'class' => 'w-24 text-center hidden xl:table-cell', 'sortable' => false],
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
            'totalAccounts' => $totalAccounts,
            'activeAccounts' => $activeAccounts,
            'inactiveAccounts' => $totalAccounts - $activeAccounts,
        ])->layout('layouts.app');
    }

    public function show(int $id): void
    {
        $this->authorizeSuperAdmin();

        $this->selectedSchool = School::with(['setting', 'account', 'fundingSources'])->findOrFail($id);
        $this->detailTab = 'pengguna';
        $this->detailModal = true;
    }

    public function activate(int $id): void
    {
        $this->authorizeSuperAdmin();

        $school = School::findOrFail($id);

        DB::transaction(function () use ($school) {
            $school->update(['status' => 'active']);

            if ($school->account) {
                $school->account->update([
                    'status' => 'active',
                ]);
            }
        });

        $this->success("Sekolah {$school->name} kini aktif kembali.");
    }

    public function confirmSuspend(int $id, string $name): void
    {
        $this->authorizeSuperAdmin();

        $this->targetSchoolId = $id;
        $this->targetSchoolName = $name;
        $this->confirmingSuspend = true;
    }

    public function suspend(): void
    {
        $this->authorizeSuperAdmin();

        if ($this->targetSchoolId) {
            $school = School::findOrFail($this->targetSchoolId);

            DB::transaction(function () use ($school) {
                $school->suspend();

                if ($school->account) {
                    $school->account->update([
                        'status' => 'suspended',
                    ]);
                }
            });

            $this->warning("Sekolah {$school->name} berhasil dibekukan sementara.");
            $this->confirmingSuspend = false;
            $this->targetSchoolId = null;
            $this->targetSchoolName = '';
        }
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
            $school = School::findOrFail($this->targetSchoolId);
            $school->delete();
            $this->error("Sekolah {$school->name} berhasil dihapus dari sistem.");

            $this->confirmingDestroy = false;
            $this->targetSchoolId = null;
        }
    }

    public function confirmResetPassword(int $schoolId): void
    {
        $this->authorizeSuperAdmin();

        $school = School::with('account')->findOrFail($schoolId);
        abort_unless($school->account, 404);

        $this->targetSchoolId = $school->id;
        $this->targetSchoolName = $school->name;
        $this->newPassword = '';
        $this->confirmingResetPassword = true;
    }

    public function resetUserPassword(): void
    {
        $this->authorizeSuperAdmin();

        if (! $this->targetSchoolId) {
            return;
        }

        $school = School::with('account')->findOrFail($this->targetSchoolId);
        abort_unless($school->account, 404);

        $this->validate([
            'newPassword' => 'required|string|min:8',
        ]);

        $school->account->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->success("Password admin sekolah {$school->name} berhasil direset.");
        $this->confirmingResetPassword = false;
        $this->newPassword = '';
        $this->targetSchoolId = null;
        $this->targetSchoolName = '';
    }

    public function confirmToggleAccount(int $schoolId): void
    {
        $this->authorizeSuperAdmin();

        $school = School::with('account')->findOrFail($schoolId);
        abort_unless($school->account, 404);

        $this->targetAccountId = $school->account->id;
        $this->targetAccountName = $school->account->name;
        $this->confirmingToggleAccount = true;
    }

    public function toggleAccount(): void
    {
        $this->authorizeSuperAdmin();

        if (! $this->targetAccountId) {
            return;
        }

        $account = User::findOrFail($this->targetAccountId);
        $account->update([
            'status' => $account->status === 'active' ? 'suspended' : 'active',
        ]);

        $this->success("Akun \"{$account->name}\" kini berstatus {$account->status}.");
        $this->confirmingToggleAccount = false;
        $this->targetAccountId = null;
        $this->targetAccountName = '';
    }
}
