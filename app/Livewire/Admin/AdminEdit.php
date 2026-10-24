<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\ResetAdminPassword;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Spatie\Activitylog\Models\Activity;

class AdminEdit extends Component
{
    use Toast, WithFileUploads;

    public User $user;

    public string $username = '';

    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $notes = null;

    public ?string $password = null;

    public string $status = 'active';

    public $photo = null;

    public bool $confirmingSuspend = false;

    public ?int $lastActivity = null;

    public function mount(User $user): void
    {
        $this->authorizeSuperAdmin();

        abort_unless($user->isAdmin(), 404);

        $this->user = $user;
        $this->username = $user->username;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->notes = $user->notes;
        $this->status = $user->status;

        if (config('session.driver') === 'database') {
            $this->lastActivity = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->max('last_activity');
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'password' => ['nullable', 'string', 'min:8'],
            'photo' => 'nullable|image|max:2048',
        ];
    }

    public function save(ResetAdminPassword $resetAdminPassword): void
    {
        $this->authorizeSuperAdmin();
        Gate::authorize('update', $this->user);

        $this->validate();

        $passwordChanged = ! empty($this->password);

        if ($passwordChanged) {
            Gate::authorize('resetPassword', $this->user);
        }

        $before = $this->user->only(['name', 'email', 'phone', 'notes', 'avatar_path']);

        DB::transaction(function () use ($passwordChanged, $resetAdminPassword) {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'notes' => $this->notes,
            ];

            if ($this->photo) {
                if ($this->user->avatar_path) {
                    Storage::disk('public')->delete($this->user->avatar_path);
                }

                $data['avatar_path'] = $this->photo->store('avatars', 'public');
            }

            $this->user->update($data);

            if ($passwordChanged) {
                $resetAdminPassword->handle(auth()->user(), $this->user, $this->password);
            }
        });

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($this->user)
            ->withProperties([
                'before' => $before,
                'after' => $this->user->only(['name', 'email', 'phone', 'notes', 'avatar_path']),
                'photo_changed' => (bool) $this->photo,
            ])
            ->log('admin-updated');

        $this->photo = null;
        $this->password = null;
        $this->user->refresh();
        $this->lastActivity = $this->resolveLastActivity();

        $this->success($passwordChanged
            ? 'Data admin dan kata sandi berhasil diperbarui.'
            : 'Data admin berhasil diperbarui.');

        $this->redirectRoute('admins.index', navigate: true);
    }

    public function confirmSuspend(): void
    {
        $this->authorizeSuperAdmin();

        abort_if(
            $this->user->id === auth()->id(),
            403,
            'Anda tidak dapat menonaktifkan akun sendiri.'
        );

        Gate::authorize('changeStatus', $this->user);

        $this->confirmingSuspend = true;
    }

    public function suspend(): void
    {
        $this->authorizeSuperAdmin();

        abort_if(
            $this->user->id === auth()->id(),
            403,
            'Anda tidak dapat menonaktifkan akun sendiri.'
        );

        Gate::authorize('changeStatus', $this->user);

        $this->user->deactivate();
        $this->user->terminateSessions();
        $this->status = 'suspended';
        $this->confirmingSuspend = false;

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($this->user)
            ->withProperties(['status' => 'suspended'])
            ->log('admin-suspended');

        $this->warning("Akun {$this->user->name} berhasil dinonaktifkan.");
    }

    public function activate(): void
    {
        $this->authorizeSuperAdmin();
        Gate::authorize('changeStatus', $this->user);

        $this->user->activate();
        $this->status = 'active';

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($this->user)
            ->withProperties(['status' => 'active'])
            ->log('admin-activated');

        $this->success("Akun {$this->user->name} kembali aktif.");
    }

    #[Computed]
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->user->name));

        return mb_strtoupper(
            mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1)
        );
    }

    #[Computed]
    public function avatarUrl(): ?string
    {
        if ($this->photo) {
            return $this->photo->temporaryUrl();
        }

        if ($this->user->avatar_path) {
            return Storage::disk('public')->url($this->user->avatar_path);
        }

        return null;
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

    #[Computed]
    public function activityHistory(): array
    {
        $createdLog = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $this->user->id)
            ->where('description', 'admin-created')
            ->latest()
            ->first();

        $updatedLog = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $this->user->id)
            ->whereIn('description', ['admin-updated', 'admin-role-updated'])
            ->latest()
            ->first();

        return [
            'created_by' => $createdLog?->causer?->name ?? '-',
            'created_at' => $this->user->created_at,
            'updated_by' => $updatedLog?->causer?->name ?? '-',
            'updated_at' => $this->user->updated_at,
        ];
    }

    #[Computed]
    public function lastLoginAt(): ?int
    {
        return $this->lastActivity ??= $this->resolveLastActivity();
    }

    private function resolveLastActivity(): ?int
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->user->id)
            ->max('last_activity');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(
            auth()->user()?->isSuperAdmin(),
            403,
            'Hanya Superadmin yang memiliki hak akses ke halaman ini.'
        );
    }

    public function render()
    {
        Gate::authorize('view', $this->user);

        return view('livewire.admin.admin-edit')->layout('layouts.app');
    }
}
