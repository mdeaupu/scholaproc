<?php

namespace App\Livewire\Dashboard;

use App\Models\GeneratedDocument;
use App\Models\Notification;
use App\Models\PackageCategory;
use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SchoolDashboard extends Component
{
    private const CATEGORY_COLORS = [
        '#2563EB',
        '#F97316',
        '#38BDF8',
        '#14B8A6',
        '#8B5CF6',
        '#FACC15',
        '#F472B6',
        '#94A3B8',
    ];

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    /** @var array<string, mixed> Pengaturan chart tren (Chart.js) untuk x-mary-chart. */
    public array $trendChart = [];

    /** @var array<string, mixed> Pengaturan chart donut kategori (Chart.js) untuk x-mary-chart. */
    public array $categoryChart = [];

    public function resetFilters(): void
    {
        $this->reset(['dateFrom', 'dateTo']);
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $school = $user->school;

        $schoolRequests = $school
            ? $school->procurementRequests()->inPeriod($this->dateFrom, $this->dateTo)
            : ProcurementRequest::whereKey(-1);

        $inProcessStatuses = [
            ProcurementRequest::STATUS_PRICE_AGREED,
            ProcurementRequest::STATUS_PROCESSING,
            ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
            ProcurementRequest::STATUS_RECEIVED,
            ProcurementRequest::STATUS_INVOICED,
            ProcurementRequest::STATUS_PAYMENT_PENDING,
        ];

        // Tren 6 bulan terakhir (tanpa filter tanggal) — untuk chart gabungan bar + garis.
        $trendLabels = [];
        $trendCounts = [];
        $trendAmounts = [];
        foreach (range(5, 0) as $monthsAgo) {
            $monthStart = Carbon::now()->subMonthsNoOverflow($monthsAgo)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $monthQuery = $school
                ? $school->procurementRequests()->whereBetween('procurement_requests.created_at', [
                    $monthStart->toDateTimeString(),
                    $monthEnd->toDateTimeString(),
                ])->getQuery()
                : ProcurementRequest::whereKey(-1);

            $trendLabels[] = $monthStart->copy()->locale('id')->isoFormat('MMM YYYY');
            $trendCounts[] = (clone $monthQuery)->count();
            $trendAmounts[] = ProcurementRequest::sumEstimatedAmount($monthQuery);
        }

        // Pengelompokan per kategori paket (null → Lainnya).
        $categoryTotals = (clone $schoolRequests)
            ->selectRaw('COALESCE(package_category_id, 0) AS category_id, COUNT(*) AS total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $categoryNames = PackageCategory::pluck('name', 'id');
        $categoryGrandTotal = (int) $categoryTotals->sum();
        $categoryDivisor = max(1, $categoryGrandTotal);

        $categoryBreakdown = $categoryTotals
            ->map(fn ($total, $categoryId) => [
                'name' => $categoryNames[$categoryId] ?? 'Lainnya',
                'count' => (int) $total,
                'percentage' => round(((int) $total / $categoryDivisor) * 100, 1),
            ])
            ->sortByDesc('count')
            ->values();

        $categoryColors = [];
        foreach ($categoryBreakdown as $index => $category) {
            $categoryColors[] = self::CATEGORY_COLORS[$index % count(self::CATEGORY_COLORS)];
        }

        $this->trendChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $trendLabels,
                'datasets' => [
                    [
                        'type' => 'bar',
                        'label' => 'Jumlah Pengadaan',
                        'data' => $trendCounts,
                        'backgroundColor' => '#93C5FD',
                        'borderRadius' => 6,
                        'yAxisID' => 'y',
                        'order' => 2,
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Total Nilai (Rp juta)',
                        'data' => array_map(fn ($amount) => round($amount / 1_000_000, 1), $trendAmounts),
                        'borderColor' => '#2563EB',
                        'backgroundColor' => '#2563EB',
                        'yAxisID' => 'y1',
                        'tension' => 0.35,
                        'pointRadius' => 3,
                        'order' => 1,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'y' => [
                        'position' => 'left',
                        'ticks' => ['precision' => 0, 'color' => '#6B7280'],
                        'grid' => ['color' => '#F3F4F6'],
                    ],
                    'y1' => [
                        'position' => 'right',
                        'ticks' => ['color' => '#6B7280'],
                        'grid' => ['drawOnChartArea' => false],
                    ],
                    'x' => ['ticks' => ['color' => '#6B7280']],
                ],
            ],
        ];

        $this->categoryChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $categoryBreakdown->pluck('name')->all(),
                'datasets' => [[
                    'data' => $categoryBreakdown->pluck('count')->all(),
                    'backgroundColor' => $categoryColors,
                    'borderWidth' => 0,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'cutout' => '72%',
                'plugins' => ['legend' => ['display' => false]],
            ],
        ];

        return view('livewire.dashboard.school-dashboard', [
            'schoolName' => $school?->name ?? 'Sekolah Belum Terpilih',
            'activeCount' => (clone $schoolRequests)->active()->count(),
            'completedCount' => (clone $schoolRequests)->completed()->count(),
            'pendingNegotiationCount' => $school
                ? ProcurementNegotiation::pending()
                    ->offeredBy(ProcurementNegotiation::OFFERED_BY_CV)
                    ->whereHas('procurementRequest', fn ($query) => $query->where('school_id', $school->id))
                    ->count()
                : 0,
            'paymentPendingCount' => (clone $schoolRequests)->whereIn('status', [
                ProcurementRequest::STATUS_INVOICED,
                ProcurementRequest::STATUS_PAYMENT_PENDING,
            ])->count(),
            'recentRequests' => (clone $schoolRequests)->with(['items', 'packageCategory'])->latest()->take(5)->get(),
            'recentDocuments' => $school
                ? GeneratedDocument::whereHas('procurementRequest', fn ($query) => $query->where('school_id', $school->id))
                    ->with('procurementRequest')
                    ->latest('generated_at')
                    ->take(5)
                    ->get()
                : collect(),
            'recentNotifications' => $school
                ? Notification::whereHas('procurementRequest', fn ($query) => $query->where('school_id', $school->id))
                    ->latest()
                    ->take(5)
                    ->get()
                : collect(),
            'totalRequests' => (clone $schoolRequests)->count(),
            'negotiationWaitingCount' => (clone $schoolRequests)
                ->where('status', ProcurementRequest::STATUS_NEGOTIATION)
                ->count(),
            'inProcessCount' => (clone $schoolRequests)->whereIn('status', $inProcessStatuses)->count(),
            'trendLabels' => $trendLabels,
            'trendCounts' => $trendCounts,
            'trendAmounts' => $trendAmounts,
            'categoryBreakdown' => $categoryBreakdown,
            'categoryColors' => $categoryColors,
            'categoryGrandTotal' => $categoryGrandTotal,
            'pendingNegotiationItems' => $school
                ? ProcurementNegotiation::pending()
                    ->offeredBy(ProcurementNegotiation::OFFERED_BY_CV)
                    ->whereHas('procurementRequest', fn ($query) => $query->where('school_id', $school->id))
                    ->with(['procurementRequestItem', 'procurementRequest.subsidiary'])
                    ->latest()
                    ->take(5)
                    ->get()
                : collect(),
            'todayLabel' => Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY'),
        ])->layout('layouts.app');
    }
}
