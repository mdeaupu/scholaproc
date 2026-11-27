<?php

namespace App\Livewire\Report;

use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\ProcurementRequest;
use App\Models\School;
use App\Models\Subsidiary;
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
        'subsidiaries' => 'Per Anak Perusahaan',
        'statuses' => 'Per Status',
    ];

    public string $period = ReportFilter::ALL;

    public string $date_from = '';

    public string $date_to = '';

    public string $school_id = '';

    public string $subsidiary_id = '';

    public string $funding_source_id = '';

    public string $budget_year_id = '';

    /** @var string */
    public string $status = '';

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
        $this->subsidiary_id = (string) request()->query('subsidiary_id', '');
        $this->funding_source_id = (string) request()->query('funding_source_id', '');
        $this->budget_year_id = (string) request()->query('budget_year_id', '');

        $status = request()->query('status', '');
        $status = is_array($status) ? ($status[0] ?? '') : (string) $status;
        $this->status = in_array($status, ProcurementRequest::reportStatuses(), true) ? $status : '';

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
        $this->reset(['period', 'date_from', 'date_to', 'school_id', 'subsidiary_id', 'funding_source_id', 'budget_year_id', 'status']);
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
            'subsidiary_id' => $this->subsidiary_id,
            'funding_source_id' => $this->funding_source_id,
            'budget_year_id' => $this->budget_year_id,
            'status' => $this->status ? [$this->status] : [],
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
            'subsidiary_id' => $this->subsidiary_id,
            'funding_source_id' => $this->funding_source_id,
            'budget_year_id' => $this->budget_year_id,
            'status' => $this->status ? [$this->status] : [],
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

        $subsidiaryOptions = Subsidiary::orderBy('name')->get()->map(fn (Subsidiary $subsidiary): array => [
            'id' => (string) $subsidiary->id,
            'name' => $subsidiary->name.(! $subsidiary->isActive() ? ' (Nonaktif)' : ''),
        ])->values()->all();

        return view('livewire.report.report-viewer', [
            'periodPresets' => collect(ReportFilter::periodPresets())
                ->map(fn ($label, $id) => ['id' => $id, 'name' => $label])
                ->values()->all(),
            'statusLabels' => collect(ProcurementRequest::statusLabels())
                ->map(fn ($label, $id) => ['id' => $id, 'name' => $label])
                ->values()->all(),
            'tabs' => self::TABS,
            'schools' => School::orderBy('name')->get()->map(fn ($s) => [
                'id' => (string) $s->id,
                'name' => $s->name,
            ])->values()->all(),
            'subsidiaries' => $subsidiaryOptions,
            'fundingSources' => FundingSource::orderBy('name')->get()->map(fn ($f) => [
                'id' => (string) $f->id,
                'name' => $f->name,
            ])->values()->all(),
            'budgetYears' => BudgetYear::orderByDesc('name')->get()->map(fn ($b) => [
                'id' => (string) $b->id,
                'name' => $b->name,
            ])->values()->all(),
            'summary' => $summary,
            'bySchool' => ProcurementRequest::reportBySchool(ProcurementRequest::forReport($filters)),
            'bySubsidiary' => ProcurementRequest::reportBySubsidiary(ProcurementRequest::forReport($filters)),
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
