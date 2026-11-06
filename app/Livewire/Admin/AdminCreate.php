<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Role;

class AdminCreate extends Component
{
    use Toast, WithFileUploads;

    public string $name = '';

    public ?string $email = null;

    public string $username = '';

    public string $password = '';

    public ?string $notes = null;

    public $photo = null;

    public function mount(): void
    {
        Gate::authorize('create', User::class);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:8',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:2048',
        ];
    }

    public function save(): void
    {
        Gate::authorize('create', User::class);

        $validated = $this->validate();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'password' => $validated['password'],
                'role' => 'admin',
                'status' => 'active',
                'notes' => $validated['notes'],
                'avatar_path' => $this->photo?->store('avatars', 'public'),
            ]);

            $user->assignRole(Role::findOrCreate('admin'));

            return $user;
        });

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'username' => $user->username,
                'role' => $user->role,
                'status' => $user->status,
            ])
            ->log('admin-created');

        $this->success('Akun Admin CV berhasil didaftarkan.');

        $this->redirectRoute('admins.index', navigate: true);
    }

    #[Computed]
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return mb_strtoupper(
            mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1)
        );
    }

    #[Computed]
    public function avatarUrl(): ?string
    {
        return $this->photo?->temporaryUrl();
    }

    #[Computed]
    public function accessList(): array
    {
        return [
            'Pengajuan',
            'Pemrosesan Pengadaan',
            'Dokumen',
            'Laporan',
            'Audit Trail',
            'Pengaturan (terbatas)',
        ];
    }

    public function render()
    {
        return view('livewire.admin.admin-create')->layout('layouts.app');
    }
}
