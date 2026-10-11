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
        return true;
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
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
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
            'supplier_id.exists' => 'Supplier yang dipilih tidak tersedia.',
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
