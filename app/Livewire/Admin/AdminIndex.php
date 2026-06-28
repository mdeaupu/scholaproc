<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class AdminIndex extends Component
{
    use WithPagination, Toast;

    public string $search = '';
    public string $filterStatus = '';
    public ?int $userId = null;
    public string $username = '';
    public string $name = '';
    public string $email = '';
    public string $password = '';

    public bool $isFormModalOpen = false;
    public bool $isDetailModalOpen = false;
    public bool $isEditMode = false;
    public ?User $selectedUser = null;

    public bool $confirmingReset = false;
    public bool $confirmingSuspend = false;
    public bool $confirmingDestroy = false;

    public ?int $targetAdminId = null;
    public string $targetAdminName = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => '']
    ];

    public function mount(): void
    {
        if (!auth()->check() || !auth()->user()->isOwner()) {
            abort(403, 'Hanya Owner/Superadmin yang memiliki hak akses ke halaman ini.');
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

    public function render()
    {
        $admins = User::query()
            ->adminCv()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('username', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus === 'active', function ($query) {
                $query->where('status', true);
            })
            ->when($this->filterStatus === 'suspended', function ($query) {
                $query->where('status', false);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.admin-index', compact('admins'))->layout('layouts.app');
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isEditMode = false;
        $this->isFormModalOpen = true;
    }

    public function store(): void
    {
        $validated = $this->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::createAdminCv($validated);

        $this->isFormModalOpen = false;
        $this->resetForm();

        $this->success('Akun Admin CV berhasil didaftarkan.');
    }

    public function show(User $user): void
    {
        $this->selectedUser = $user;
        $this->isDetailModalOpen = true;
    }

    public function edit(User $user): void
    {
        $this->resetValidation();
        $this->userId = $user->id;
        $this->username = $user->username;
        $this->name = $user->name;
        $this->email = $user->email ?? '';
        $this->password = '';

        $this->isEditMode = true;
        $this->isFormModalOpen = true;
    }

    public function update(): void
    {
        $user = User::findOrFail($this->userId);

        $validated = $this->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
        ]);

        $user->updateAdminCv($validated);

        $this->isFormModalOpen = false;
        $this->resetForm();

        $this->success('Profil Admin CV berhasil diubah.');
    }

    public function confirmReset(int $id, string $name): void
    {
        $this->targetAdminId = $id;
        $this->targetAdminName = $name;
        $this->confirmingReset = true;
    }

    public function confirmSuspend(int $id, string $name): void
    {
        $this->targetAdminId = $id;
        $this->targetAdminName = $name;
        $this->confirmingSuspend = true;
    }

    public function confirmDestroy(int $id, string $name): void
    {
        $this->targetAdminId = $id;
        $this->targetAdminName = $name;
        $this->confirmingDestroy = true;
    }

    public function activate(int $id): void
    {
        $user = User::findOrFail($id);
        $user->activate();

        $this->success("Status akun {$user->name} kembali aktif.");
    }

    public function suspend(): void
    {
        if ($this->targetAdminId) {
            $user = User::findOrFail($this->targetAdminId);
            $user->deactivate();
            $this->confirmingSuspend = false;

            $this->warning("Status akun {$user->name} berhasil dibekukan.");
        }
    }

    public function resetPasswordAction(User $user): void
    {
        if ($this->targetAdminId) {
            $user = User::findOrFail($this->targetAdminId);
            $user->resetPassword('Password123!');
            $this->confirmingReset = false;

            $this->success("Password untuk {$user->name} di-reset ke default: Password123!");
        }
    }

    public function destroy(User $user): void
    {
        if ($this->targetAdminId) {
            $user = User::findOrFail($this->targetAdminId);
            $user->delete();
            $this->confirmingDestroy = false;

            $this->error("Akun Admin CV {$user->name} berhasil dihapus.");
        }
    }

    private function resetForm(): void
    {
        $this->userId = null;
        $this->username = '';
        $this->name = '';
        $this->email = '';
        $this->password = '';
    }
}
