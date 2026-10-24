<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Spatie\Activitylog\Models\Activity;

class AdminIndex extends Component
{
    use Toast, WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public bool $isDetailModalOpen = false;

    public ?User $selectedUser = null;

    public string $detailTab = 'umum';

    public ?int $selectedLastActivity = null;

    /** @var array<int, array{description: string, label: string, causer: string, created_at: string}> */
    public array $selectedActivities = [];

    public bool $confirmingDestroy = false;

    public ?int $targetAdminId = null;

    public string $targetAdminName = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
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
        Gate::authorize('viewAny', User::class);

        $managedQuery = User::query()->where('role', 'admin');

        $stats = [
            'total' => (clone $managedQuery)->count(),
            'active' => (clone $managedQuery)->where('status', 'active')->count(),
            'inactive' => (clone $managedQuery)->where('status', 'suspended')->count(),
        ];

        $stats['active_percent'] = $stats['total'] > 0
            ? (int) round(($stats['active'] / $stats['total']) * 100)
            : 0;

        $stats['inactive_percent'] = $stats['total'] > 0
            ? (int) round(($stats['inactive'] / $stats['total']) * 100)
            : 0;

        $admins = (clone $managedQuery)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterStatus === 'active', function ($query) {
                $query->where('status', 'active');
            })
            ->when($this->filterStatus === 'suspended', function ($query) {
                $query->where('status', 'suspended');
            })
            ->withCount(['histories', 'negotiations', 'verifiedRequests', 'verificationFiles'])
            ->latest()
            ->paginate(10);

        $adminIds = $admins->pluck('id');

        $lastActivities = config('session.driver') === 'database'
            ? DB::table(config('session.table', 'sessions'))
                ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
                ->whereIn('user_id', $adminIds)
                ->groupBy('user_id')
                ->pluck('last_activity', 'user_id')
            : collect();

        $causerActivityIds = Activity::query()
            ->where('causer_type', (new User)->getMorphClass())
            ->whereIn('causer_id', $adminIds)
            ->distinct()
            ->pluck('causer_id')
            ->flip();

        if ($admins->firstItem() !== null) {
            $admins->getCollection()->each(function (User $admin, int $index) use ($admins, $lastActivities, $causerActivityIds) {
                $admin->setAttribute('row_no', $admins->firstItem() + $index);
                $admin->setAttribute('last_activity', $lastActivities[$admin->id] ?? null);
                $admin->setAttribute(
                    'has_activity_history',
                    $admin->histories_count > 0
                        || $admin->negotiations_count > 0
                        || $admin->verified_requests_count > 0
                        || $admin->verification_files_count > 0
                        || $causerActivityIds->has($admin->id)
                );
            });
        }

        return view('livewire.admin.admin-index', compact('admins', 'stats'))->layout('layouts.app');
    }

    private function findManagedUser(int $id): User
    {
        return User::query()
            ->where('role', 'admin')
            ->findOrFail($id);
    }

    public function show(int $id): void
    {
        $user = $this->findManagedUser($id);
        Gate::authorize('view', $user);

        $this->selectedUser = $user;
        $this->detailTab = 'umum';
        $this->selectedLastActivity = config('session.driver') === 'database'
            ? DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->max('last_activity')
            : null;

        $this->selectedActivities = Activity::query()
            ->with('causer')
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Activity $activity) => [
                'description' => $activity->description,
                'label' => $this->activityLabel($activity->description),
                'causer' => $activity->causer?->name ?? 'Sistem',
                'created_at' => $activity->created_at?->format('d M Y, H:i') ?? '-',
            ])
            ->all();

        $this->isDetailModalOpen = true;
    }

    private function activityLabel(string $description): string
    {
        return match ($description) {
            'admin-created' => 'Akun admin dibuat',
            'admin-updated' => 'Data admin diperbarui',
            'admin-role-updated' => 'Hak akses admin diperbarui',
            'admin-activated' => 'Akun admin diaktifkan',
            'admin-suspended' => 'Akun admin dinonaktifkan',
            'admin-deleted' => 'Akun admin dihapus',
            'admin-password-reset' => 'Reset Kata Sandi',
            default => str($description)->replace(['-', '_'], ' ')->title()->toString(),
        };
    }

    public function confirmDestroy(int $id): void
    {
        $user = $this->findManagedUser($id);
        Gate::authorize('delete', $user);

        $this->targetAdminId = $user->id;
        $this->targetAdminName = $user->name;
        $this->confirmingDestroy = true;
    }

    public function destroy(): void
    {
        if (! $this->targetAdminId) {
            return;
        }

        $user = $this->findManagedUser($this->targetAdminId);
        Gate::authorize('delete', $user);

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['username' => $user->username])
            ->log('admin-deleted');

        $user->terminateSessions();
        $user->delete();
        $this->confirmingDestroy = false;

        $this->error("Akun Admin CV {$user->name} berhasil dihapus.");
    }
}
