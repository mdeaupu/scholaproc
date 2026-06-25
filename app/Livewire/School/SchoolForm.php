<?php

namespace App\Livewire\School;

use App\Models\School;
use Livewire\Component;
use Mary\Traits\Toast;

class SchoolForm extends Component
{
    use Toast;

    public ?School $school = null;
    public bool $isEdit = false;
    public string $npsn = '';
    public string $name = '';
    public string $address = '';
    public string $postal_code = '';
    public string $phone_number = '';
    public string $email = '';
    public string $status = 'active';
    public string $kop_pusat = '';
    public string $kop_provinsi = '';
    public string $kop_sub_wilayah = '';

    public function mount(?School $school = null)
    {
        if ($school && $school->exists) {
            $this->school = $school;
            $this->isEdit = true;

            $this->fill($school->toArray());

            if ($school->setting) {
                $this->kop_pusat = $school->setting->kop_pusat;
                $this->kop_provinsi = $school->setting->kop_provinsi;
                $this->kop_sub_wilayah = $school->setting->kop_sub_wilayah ?? '';
            }
        }
    }

    protected function rules(): array
    {
        return [
            'npsn' => 'required|numeric|digits:8|unique:schools,npsn,' . ($this->school->id ?? 'NULL'),
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'postal_code' => 'nullable|string|max:10',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'required|in:active,suspended',
            'kop_pusat' => 'required|string|max:255',
            'kop_provinsi' => 'required|string|max:255',
            'kop_sub_wilayah' => 'nullable|string|max:255',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        if ($this->isEdit) {
            $this->school->update([
                'npsn' => $this->npsn,
                'name' => $this->name,
                'address' => $this->address,
                'postal_code' => $this->postal_code,
                'phone_number' => $this->phone_number,
                'email' => $this->email,
                'status' => $this->status,
            ]);

            $this->school->setting()->updateOrCreate(
                ['school_id' => $this->school->id],
                [
                    'kop_pusat' => $this->kop_pusat,
                    'kop_provinsi' => $this->kop_provinsi,
                    'kop_sub_wilayah' => $this->kop_sub_wilayah,
                ]
            );

            session()->flash('toast_success', 'Data sekolah dan kop surat berhasil diperbarui!');
        } else {
            $newSchool = School::create([
                'npsn' => $this->npsn,
                'name' => $this->name,
                'address' => $this->address,
                'postal_code' => $this->postal_code,
                'phone_number' => $this->phone_number,
                'email' => $this->email,
                'status' => $this->status,
            ]);

            $newSchool->setting()->create([
                'kop_pusat' => $this->kop_pusat,
                'kop_provinsi' => $this->kop_provinsi,
                'kop_sub_wilayah' => $this->kop_sub_wilayah,
            ]);

            session()->flash('toast_success', 'Sekolah baru berhasil ditambahkan!');
        }

        $this->redirectRoute('schools.index', navigate: true);
    }
    public function render()
    {
        return view('livewire.school.school-form')->title($this->isEdit ? 'Edit Sekolah — Admin' : 'Tambah Sekolah Baru — Admin')->layout('layouts.app');
    }
}
