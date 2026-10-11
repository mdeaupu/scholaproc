<?php

namespace App\Livewire\Report;

use App\Models\ProcurementRequest;
use App\Models\School;
use App\Models\Supplier;
use App\Support\ReportFilter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

/**
 * M13a — preview laporan yang bisa difilter tanpa reload halaman.
 *
 * Angka di layar disusun dari method statis ProcurementRequest yang sama dengan
 * ReportExportService, dan filter dinormalisasi ReportFilter yang sama dengan
 * ReportFilterRequest. Karena itu hasil download selalu identik dengan preview.
 */
class ReportViewer extends Component
{
    use Toast, WithPagination;

    public const TABS = [
        'summary' => 'Ringkasan',
        'transactions' => 'Transaksi',
        'schools' => 'Per Sekolah',
        'suppliers' => 'Per Supplier',
        'statuses' => 'Per Status',
    ];

    public string $period = ReportFilter::ALL;

    public string $date_from = '';

    public string $date_to = '';

    public string $school_id = '';

    public string $supplier_id = '';

    /** @var list<string> */
    public array $status = [];

    public string $tab = 'summary';

    public int $perPage = 15;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin(),
            403
        );

        $this->period = ReportFilter::isValidPeriod(request()->query('period'))
            ? (string) request()->query('period')
            : ReportFilter::defaultPeriod();

        if ($this->period === ReportFilter::CUSTOM) {
            $this->date_from = (string) request()->query('date_from', '');
            $this->date_to = (string) request()->query('date_to', '');
        }

        $this->school_id = (string) request()->query('school_id', '');
        $this->supplier_id = (string) request()->query('supplier_id', '');

        $status = request()->query('status', []);
        $status = is_string($status) ? [$status] : (array) $status;
        $this->status = array_values(array_intersect($status, ProcurementRequest::reportStatuses()));

        $tab = (string) request()->query('tab', 'summary');
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'summary';
    }

    /**
     * Setiap perubahan filter mengembalikan ke halaman pertama & tab transaksi
     * yang paling sering dipakai setelah menyaring.
     */
    public function updated(string $property): void
    {
        if ($property === 'period') {
            $this->reset(['date_from', 'date_to']);
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['period', 'date_from', 'date_to', 'school_id', 'supplier_id', 'status']);
        $this->period = ReportFilter::defaultPeriod();
        $this->resetPage();
        $this->info('Filter laporan dikosongkan.');
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return ReportFilter::normalize([
            'period' => $this->period,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'school_id' => $this->school_id,
            'supplier_id' => $this->supplier_id,
            'status' => $this->status,
        ]);
    }

    /**
     * URL download yang membawa filter yang sedang aktif.
     */
    public function exportUrl(string $format): string
    {
        $query = array_filter([
            'period' => $this->period,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'school_id' => $this->school_id,
            'supplier_id' => $this->supplier_id,
            'status' => $this->status,
        ], fn ($value) => $value !== '' && $value !== [] && $value !== null);

        return route('reports.export.'.$format, $query);
    }

    public function periodLabel(): string
    {
        return ReportFilter::periodLabel($this->filters());
    }

    public function render()
    {
        $filters = $this->filters();
        $summary = ProcurementRequest::reportSummary(ProcurementRequest::forReport($filters));

        return view('livewire.report.report-viewer', [
            'periodPresets' => ReportFilter::periodPresets(),
            'statusLabels' => ProcurementRequest::statusLabels(),
            'tabs' => self::TABS,
            'schools' => School::orderBy('name')->pluck('name', 'id'),
            'suppliers' => Supplier::orderBy('company_name')->pluck('company_name', 'id'),
            'summary' => $summary,
            'bySchool' => ProcurementRequest::reportBySchool(ProcurementRequest::forReport($filters)),
            'bySupplier' => ProcurementRequest::reportBySupplier(ProcurementRequest::forReport($filters)),
            'byStatus' => ProcurementRequest::reportByStatus(ProcurementRequest::forReport($filters)),
            'transactions' => $this->paginateTransactions($filters, $summary),
        ])->layout('layouts.app');
    }

    /**
     * Total baris memakai reportSummary() yang sudah dihitung untuk halaman ini,
     * sehingga tabel detail tidak memerlukan query COUNT kedua.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $summary
     * @return LengthAwarePaginator<int, Collection<int, array<string, mixed>>>
     */
    protected function paginateTransactions(array $filters, array $summary): LengthAwarePaginator
    {
        $page = (int) $this->getPage();
        $perPage = max($this->perPage, 1);

        return new LengthAwarePaginator(
            ProcurementRequest::reportTransactions(
                ProcurementRequest::forReport($filters),
                $perPage,
                ($page - 1) * $perPage
            ),
            (int) $summary['total_transactions'],
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}
