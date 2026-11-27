<?php

namespace App\Http\Requests;

use App\Models\ProcurementRequest;
use App\Support\ReportFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi query string untuk download laporan. Livewire ReportViewer memakai
 * ReportFilter yang sama, sehingga hasil export identik dengan preview.
 */
class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->isSuperAdmin() || $this->user()?->isAdmin());
    }

    protected function prepareForValidation(): void
    {
        if ($this->status === '' || $this->status === []) {
            $this->merge(['status' => null]);
        } elseif (is_string($this->status)) {
            $this->merge(['status' => [$this->status]]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(array_keys(ReportFilter::periodPresets()))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')->whereNull('deleted_at')],
            'subsidiary_id' => ['nullable', 'integer', Rule::exists('subsidiaries', 'id')],
            'funding_source_id' => ['nullable', 'integer', Rule::exists('funding_sources', 'id')],
            'budget_year_id' => ['nullable', 'integer', Rule::exists('budget_years', 'id')],
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in(ProcurementRequest::reportStatuses())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.in' => 'Preset periode tidak dikenal.',
            'date_from.date_format' => 'Tanggal awal harus berformat YYYY-MM-DD.',
            'date_to.date_format' => 'Tanggal akhir harus berformat YYYY-MM-DD.',
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
            'school_id.exists' => 'Sekolah yang dipilih tidak tersedia.',
            'subsidiary_id.exists' => 'Anak Perusahaan yang dipilih tidak tersedia.',
            'funding_source_id.exists' => 'Sumber Dana yang dipilih tidak tersedia.',
            'budget_year_id.exists' => 'Tahun Anggaran yang dipilih tidak tersedia.',
            'status.*.in' => 'Status yang dipilih tidak dikenal.',
        ];
    }

    /**
     * Filter ternormalisasi untuk scopeForReport().
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return ReportFilter::normalize($this->query());
    }
}
