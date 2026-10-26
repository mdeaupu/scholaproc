<?php

namespace App\Livewire\Subsidiary;

use App\Models\Subsidiary;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class SubsidiaryForm extends Component
{
    use Toast;

    public ?Subsidiary $subsidiary = null;

    public string $name = '';

    public string $npwp = '';

    public string $director_name = '';

    public ?string $pic_name = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $nib = null;

    public ?string $director_nik = null;

    public ?string $director_npwp = null;

    public ?string $director_phone = null;

    public ?string $director_address = null;

    public ?string $commissioner_name = null;

    public ?string $commissioner_nik = null;

    public ?string $commissioner_address = null;

    public bool $isEdit = false;

    public string $activeTab = 'umum';

    public string $status = Subsidiary::STATUS_ACTIVE;

    public bool $confirmingStatusDeactivation = false;

    public function mount(?Subsidiary $subsidiary = null): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if ($subsidiary && $subsidiary->exists) {
            $subsidiary->load([
                'legalDocuments',
                'procurementRequests.packageCategory',
                'procurementRequests.school',
            ]);

            $this->subsidiary = $subsidiary;
            $this->isEdit = true;
            $this->status = $subsidiary->status;

            foreach ([
                'name',
                'npwp',
                'director_name',
                'pic_name',
                'email',
                'phone',
                'address',
                'nib',
                'director_nik',
                'director_npwp',
                'director_phone',
                'director_address',
                'commissioner_name',
                'commissioner_nik',
                'commissioner_address',
            ] as $field) {
                $this->{$field} = $subsidiary->{$field};
            }
        }
    }

    public function getLegalDocumentsProperty()
    {
        return $this->subsidiary?->legalDocuments ?? collect();
    }

    public function getProjectsProperty()
    {
        if (! $this->subsidiary) {
            return collect();
        }

        return $this->subsidiary->procurementRequests
            ->sortByDesc('created_at')
            ->values();
    }

    public function getSummaryProperty(): array
    {
        if (! $this->subsidiary) {
            return [];
        }

        return [
            'total_projects' => $this->subsidiary->procurementRequests->count(),
            'total_project_value' => $this->subsidiary->totalProjectValue(),
        ];
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'npwp' => [
                'required',
                'string',
                'max:30',
                Rule::unique('subsidiaries', 'npwp')->ignore($this->subsidiary?->id),
            ],
            'director_name' => 'required|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'nib' => 'nullable|string|max:50',
            'director_nik' => 'nullable|string|size:16',
            'director_npwp' => 'nullable|string|max:30',
            'director_phone' => 'nullable|string|max:20',
            'director_address' => 'nullable|string',
            'commissioner_name' => 'nullable|string|max:255',
            'commissioner_nik' => 'nullable|string|size:16',
            'commissioner_address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function save()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Hanya Superadmin yang dapat mengelola Anak Perusahaan.');

        $validated = $this->validate();

        if ($this->isEdit) {
            $this->subsidiary->update($validated);
            $message = "Data Anak Perusahaan {$this->name} berhasil diperbarui.";
        } else {
            Subsidiary::create($validated);
            $message = "Anak Perusahaan {$this->name} berhasil ditambahkan.";
        }

        $this->success($message);

        return $this->redirectRoute('superadmin.subsidiaries.index', navigate: true);
    }

    public function confirmDeactivate(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_unless($this->isEdit && $this->subsidiary, 404);

        $this->confirmingStatusDeactivation = true;
    }

    public function deactivate(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_unless($this->isEdit && $this->subsidiary, 404);

        $this->subsidiary->changeStatus(Subsidiary::STATUS_INACTIVE);
        $this->status = Subsidiary::STATUS_INACTIVE;
        $this->confirmingStatusDeactivation = false;
        $this->warning("Anak Perusahaan {$this->subsidiary->name} berhasil dinonaktifkan.");
    }

    public function activate(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_unless($this->isEdit && $this->subsidiary, 404);

        $this->subsidiary->changeStatus(Subsidiary::STATUS_ACTIVE);
        $this->status = Subsidiary::STATUS_ACTIVE;
        $this->success("Anak Perusahaan {$this->subsidiary->name} berhasil diaktifkan.");
    }

    public function render()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('livewire.subsidiary.subsidiary-form', [
            'legalDocuments' => $this->legalDocuments,
            'projects' => $this->projects,
            'summary' => $this->summary,
        ])->layout('layouts.app');
    }
}
