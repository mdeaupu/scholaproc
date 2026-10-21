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

    public function render()
    {
        $this->authorizeSuperAdmin();

        $schools = School::with(['setting', 'account'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('npsn', 'like', '%'.$this->search.'%');
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
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right w-48', 'sortable' => false],
        ];

        return view('livewire.school.school-index', [
            'schools' => $schools,
            'headers' => $headers,
        ])->layout('layouts.app');
    }

    public function show(int $id): void
    {
        $this->authorizeSuperAdmin();

        $this->selectedSchool = School::with(['setting', 'account', 'fundingSources'])->findOrFail($id);
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
