<?php

namespace App\Livewire\Supplier;

use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class SupplierForm extends Component
{
    use Toast;

    public ?Supplier $supplier = null;
    public string $company_name = '';
    public string $pic_name = '';
    public string $npwp = '';
    public string $nib = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';

    public string $director_name = '';
    public string $director_nik = '';
    public ?string $director_npwp = null;
    public ?string $director_phone = null;
    public ?string $director_address = null;

    public ?string $commissioner_name = null;
    public ?string $commissioner_nik = null;
    public ?string $commissioner_address = null;

    public bool $isEdit = false;

    public function mount(?Supplier $supplier = null): void
    {
        if ($supplier && $supplier->exists) {
            $this->supplier = $supplier;
            $this->isEdit = true;
            $this->company_name = $supplier->company_name;
            $this->pic_name = $supplier->pic_name;
            $this->npwp = $supplier->npwp;
            $this->nib = $supplier->nib;
            $this->phone = $supplier->phone;
            $this->email = $supplier->email ?? '';
            $this->address = $supplier->address ?? '';

            $this->director_name = $supplier->director_name;
            $this->director_nik = $supplier->director_nik;
            $this->director_npwp = $supplier->director_npwp;
            $this->director_phone = $supplier->director_phone;
            $this->director_address = $supplier->director_address;

            $this->commissioner_name = $supplier->commissioner_name;
            $this->commissioner_nik = $supplier->commissioner_nik;
            $this->commissioner_address = $supplier->commissioner_address;
        }
    }

    protected function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'pic_name' => 'required|string|max:255',
            'npwp' => [
                'required',
                'string',
                'max:30',
                Rule::unique('suppliers', 'npwp')->ignore($this->supplier?->id)
            ],
            'nib' => [
                'required',
                'string',
                'max:50',
                Rule::unique('suppliers', 'nib')->ignore($this->supplier?->id)
            ],
            'phone' => 'required|string|max:20',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($this->supplier?->id)
            ],
            'address' => 'required|string',

            'director_name' => 'required|string|max:255',
            'director_nik' => 'required|string|size:16',
            'director_npwp' => 'nullable|string|max:30',
            'director_phone' => 'nullable|string|max:20',
            'director_address' => 'nullable|string',

            'commissioner_name' => 'nullable|string|max:255',
            'commissioner_nik' => 'nullable|string|size:16',
            'commissioner_address' => 'nullable|string',
        ];
    }

    public function save()
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki otoritas untuk mengelola data supplier.');
        }

        $validated = $this->validate();

        if ($this->isEdit) {
            $this->supplier->update($validated);
            $message = "Data supplier {$this->company_name} berhasil diperbarui.";
        } else {
            Supplier::create($validated);
            $message = "Supplier {$this->company_name} berhasil ditambahkan ke dalam sistem.";
        }

        session()->flash('toast_success', $message);

        $route = auth()->user()->isSuperAdmin() ? 'owner.suppliers.index' : 'cv.suppliers.index';
        return $this->redirectRoute($route, navigate: true);
    }

    public function render()
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdminCv()) {
            abort(403);
        }

        return view('livewire.supplier.supplier-form')->layout('layouts.app');
    }
}
