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
    public string $director_name = '';
    public string $director_nik = '';
    public string $npwp = '';
    public string $nib = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public bool $isEdit = false;

    public function mount(?Supplier $supplier = null): void
    {
        if ($supplier && $supplier->exists) {
            $this->supplier = $supplier;
            $this->isEdit = true;
            $this->company_name = $supplier->company_name;
            $this->pic_name = $supplier->pic_name;
            $this->director_name = $supplier->director_name;
            $this->director_nik = $supplier->director_nik;
            $this->npwp = $supplier->npwp;
            $this->nib = $supplier->nib;
            $this->phone = $supplier->phone;
            $this->email = $supplier->email ?? '';
            $this->address = $supplier->address ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'pic_name' => 'required|string|max:255',
            'director_name' => 'required|string|max:255',
            'director_nik' => 'required|string|size:16',
            'npwp' => [
                'required',
                'string',
                'max:50',
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
        ];
    }

    public function save()
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isAdminCv()) {
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

        $route = auth()->user()->isOwner() ? 'owner.suppliers.index' : 'cv.suppliers.index';
        return $this->redirectRoute($route, navigate: true);
    }

    public function render()
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isAdminCv()) {
            abort(403);
        }

        return view('livewire.supplier.supplier-form')->layout('layouts.app');
    }
}
