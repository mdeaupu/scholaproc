<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\SubmitProcurementRequest;
use App\Models\BudgetYear;
use App\Models\ItemUnit;
use App\Models\PackageCategory;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class ProcurementRequestForm extends Component
{
    use Toast;

    public ?ProcurementRequest $procurement = null;

    public bool $isEdit = false;

    public string $package_category_id = '';

    public string $budget_year_id = '';

    public string $funding_source_id = '';

    public array $items = [];

    public string $previousUrl = '';

    public function mount(?int $id = null): mixed
    {
        $user = auth()->user();
        if (! $user?->isSchool() || ! $user->school_id) {
            abort(403);
        }

        $this->previousUrl = url()->previous() ?: route('procurement.index');
        if ($this->previousUrl === url()->current()) {
            $this->previousUrl = route('procurement.index');
        }

        if ($id !== null) {
            $this->isEdit = true;
            $this->procurement = $this->schoolRequests()->findOrFail($id);

            if (! in_array($this->procurement->status, [
                ProcurementRequest::STATUS_DRAFT,
                ProcurementRequest::STATUS_NEEDS_REVISION,
            ], true)) {
                session()->flash('error', 'Pengajuan tidak dapat diedit karena sudah diproses.');

                return $this->redirectRoute('procurement.index', navigate: true);
            }

            $this->package_category_id = (string) $this->procurement->package_category_id;
            $this->budget_year_id = (string) $this->procurement->budget_year_id;
            $this->funding_source_id = (string) $this->procurement->funding_source_id;
            $this->items = $this->procurement->items->map(fn ($item) => [
                'id' => (string) $item->id,
                'key' => 'item-'.$item->id,
                'item_name' => $item->item_name,
                'specification' => in_array($item->specification, [null, '-'], true) ? '' : $item->specification,
                'quantity' => (string) $item->quantity,
                'unit_id' => (string) $item->unit_id,
                'estimated_price' => (string) $item->estimated_price,
            ])->all();
        } else {
            $this->addItem();
        }

        return null;
    }

    public function cancel()
    {
        return $this->redirect($this->previousUrl, navigate: true);
    }

    public function getEstimatedSubtotalProperty(): float
    {
        return collect($this->items)->sum(
            fn (array $item): float => (float) ($item['quantity'] ?? 0) * (float) ($item['estimated_price'] ?? 0)
        );
    }

    public function getTotalItemsProperty(): int
    {
        return count($this->items);
    }

    public function addItem(): void
    {
        $this->authorizeSchool();

        $this->items[] = [
            'id' => null,
            'key' => (string) Str::uuid(),
            'item_name' => '',
            'specification' => '',
            'quantity' => '1',
            'unit_id' => '',
            'estimated_price' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        $this->authorizeSchool();

        if (count($this->items) <= 1) {
            $this->error('Minimal harus ada 1 baris barang/jasa.');

            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save()
    {
        return $this->persist(submit: false);
    }

    public function submitRequest()
    {
        return $this->persist(submit: true);
    }

    public function render()
    {
        $user = auth()->user();
        $school = $user->school;
        abort_unless($school instanceof School, 403);

        return view('livewire.procurement.procurement-request-form', [
            'school' => $school,
            'packageCategories' => PackageCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'fundingSources' => $school->fundingSources()->where('funding_sources.is_active', true)->orderBy('name')->get(),
            'budgetYears' => BudgetYear::query()->where('is_active', true)->orderByDesc('start_date')->get(),
            'itemUnits' => ItemUnit::query()->where('is_active', true)->orderBy('name')->get(),
            'requestDate' => $this->procurement?->created_at ?? now(),
        ])->layout('layouts.app');
    }

    private function persist(bool $submit)
    {
        $this->authorizeSchool();

        $this->normalizeItemInputs();

        $validated = $this->validate($submit ? $this->submitRules() : $this->draftRules());

        $packageCategoryId = $this->nullableId($validated['package_category_id'] ?? null);
        $budgetYearId = $this->nullableId($validated['budget_year_id'] ?? null);
        $fundingSourceId = $this->nullableId($validated['funding_source_id'] ?? null);
        $itemRows = $submit
            ? array_values($validated['items'] ?? [])
            : $this->draftItemRows($validated['items'] ?? []);

        $user = auth()->user();
        $school = $user->school;
        if ($submit && $fundingSourceId !== null && ! $school->fundingSources()
            ->where('funding_sources.id', $fundingSourceId)
            ->where('funding_sources.is_active', true)
            ->exists()) {
            $this->addError('funding_source_id', 'Pilih sumber dana aktif yang telah ditetapkan untuk sekolah Anda.');

            return null;
        }

        if ($this->isEdit) {
            $this->procurement = $this->schoolRequests()->findOrFail($this->procurement?->id);
            abort_unless(in_array($this->procurement->status, [
                ProcurementRequest::STATUS_DRAFT,
                ProcurementRequest::STATUS_NEEDS_REVISION,
            ], true), 403);
        }

        $request = DB::transaction(function () use ($packageCategoryId, $budgetYearId, $fundingSourceId, $itemRows, $user, $submit): ProcurementRequest {
            $request = $this->isEdit
                ? $this->schoolRequests()
                    ->whereKey($this->procurement?->id)
                    ->lockForUpdate()
                    ->firstOrFail()
                : ProcurementRequest::create([
                    'uuid' => (string) Str::uuid(),
                    'school_id' => $user->school_id,
                    'status' => ProcurementRequest::STATUS_DRAFT,
                    'package_category_id' => $packageCategoryId,
                    'budget_year_id' => $budgetYearId,
                    'funding_source_id' => $fundingSourceId,
                ]);

            if ($this->isEdit) {
                abort_unless(in_array($request->status, [
                    ProcurementRequest::STATUS_DRAFT,
                    ProcurementRequest::STATUS_NEEDS_REVISION,
                ], true), 403);
            }

            $isRevision = $request->status === ProcurementRequest::STATUS_NEEDS_REVISION;
            $revisionChanges = [];

            if ($this->isEdit) {
                $original = $request->getOriginal();
                $request->update([
                    'package_category_id' => $packageCategoryId,
                    'budget_year_id' => $budgetYearId,
                    'funding_source_id' => $fundingSourceId,
                ]);

                if ($isRevision) {
                    $revisionChanges['request'] = $this->changedValues($request, $original);
                }
            } else {
                $request->recordHistory($user, ProcurementRequest::STATUS_DRAFT, 'Draf pengajuan dibuat.');
            }

            $existingItems = $request->items()->get()->keyBy('id');
            $retainedItemIds = [];
            $itemChanges = [
                'created' => [],
                'updated' => [],
                'deleted' => [],
            ];

            foreach ($itemRows as $index => $itemData) {
                $attributes = [
                    'line_number' => $index + 1,
                    'item_name' => $this->nullableString($itemData['item_name'] ?? null),
                    'specification' => $this->nullableString($itemData['specification'] ?? null),
                    'quantity' => $this->nullableInt($itemData['quantity'] ?? null),
                    'unit_id' => $this->nullableId($itemData['unit_id'] ?? null),
                    'estimated_price' => $this->nullableNumber($itemData['estimated_price'] ?? null),
                ];

                if (! empty($itemData['id'])) {
                    $item = $existingItems->get((int) $itemData['id']);
                    abort_unless($item instanceof ProcurementRequestItem, 403);

                    $retainedItemIds[] = $item->id;
                    $original = $item->getOriginal();
                    $item->update($attributes);

                    if ($isRevision) {
                        $changes = $this->changedValues($item, $original);
                        if ($changes !== []) {
                            $itemChanges['updated'][$item->id] = $changes;
                        }
                    }

                    continue;
                }

                $item = $request->items()->create($attributes);
                if ($isRevision) {
                    $itemChanges['created'][] = [
                        'id' => $item->id,
                        'values' => $this->auditValues($item->getAttributes()),
                    ];
                }
            }

            $removedItems = $existingItems->except($retainedItemIds);
            foreach ($removedItems as $item) {
                if ($isRevision) {
                    $itemChanges['deleted'][$item->id] = $this->auditValues($item->getOriginal());
                }
                $item->delete();
            }

            if ($isRevision && ($revisionChanges['request'] !== [] || array_filter($itemChanges))) {
                $request->recordHistory(
                    $user,
                    ProcurementRequest::STATUS_NEEDS_REVISION,
                    'Perubahan revisi: '.json_encode([
                        'request' => $revisionChanges['request'],
                        'items' => $itemChanges,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
                );
            }

            if ($submit) {
                $request = app(SubmitProcurementRequest::class)->handle($request, $user);
            }

            return $request;
        });

        session()->flash(
            'toast_success',
            $submit ? 'Pengajuan berhasil dikirim untuk diverifikasi.' : 'Draf pengajuan berhasil disimpan.'
        );

        return $this->redirectRoute('procurement.show', ['procurementRequest' => $request->id], navigate: true);
    }

    private function draftRules(): array
    {
        return [
            'package_category_id' => [
                'nullable',
                Rule::when(filled($this->package_category_id), [
                    'integer',
                    Rule::exists('package_categories', 'id'),
                ]),
            ],
            'budget_year_id' => [
                'nullable',
                Rule::when(filled($this->budget_year_id), [
                    'integer',
                    Rule::exists('budget_years', 'id'),
                ]),
            ],
            'funding_source_id' => [
                'nullable',
                Rule::when(filled($this->funding_source_id), [
                    'integer',
                    Rule::exists('funding_sources', 'id'),
                ]),
            ],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer', 'distinct'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'items.*.unit_id' => [
                'nullable',
                'integer',
                Rule::exists('item_units', 'id'),
            ],
            'items.*.estimated_price' => ['nullable', 'numeric', 'max:9999999999999.99'],
        ];
    }

    private function submitRules(): array
    {
        return [
            'package_category_id' => [
                'required',
                Rule::exists('package_categories', 'id')->where('is_active', true),
            ],
            'budget_year_id' => [
                'required',
                Rule::exists('budget_years', 'id')->where('is_active', true),
            ],
            'funding_source_id' => [
                'required',
                Rule::exists('funding_sources', 'id')->where('is_active', true),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'distinct'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.specification' => ['required', 'string', 'max:1000', 'not_in:-'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'items.*.unit_id' => [
                'required',
                Rule::exists('item_units', 'id')->where('is_active', true),
            ],
            'items.*.estimated_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
        ];
    }

    private function normalizeItemInputs(): void
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['item_name'] = trim((string) ($item['item_name'] ?? ''));
            $this->items[$index]['specification'] = trim((string) ($item['specification'] ?? ''));

            foreach (['quantity', 'unit_id', 'estimated_price'] as $key) {
                if (blank($item[$key] ?? null)) {
                    $this->items[$index][$key] = null;
                }
            }
        }
    }

    private function draftItemRows(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => $this->hasItemData($item))
            ->values()
            ->all();
    }

    private function hasItemData(array $item): bool
    {
        return filled($item['id'] ?? null)
            || filled($item['item_name'] ?? null)
            || filled($item['specification'] ?? null)
            || filled($item['unit_id'] ?? null)
            || filled($item['estimated_price'] ?? null)
            || (filled($item['quantity'] ?? null) && (int) $item['quantity'] !== 1);
    }

    private function nullableId(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableNumber(mixed $value): mixed
    {
        return $value === null || $value === '' ? null : $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function changedValues(Model $model, array $original): array
    {
        $changes = [];

        foreach ($model->getChanges() as $attribute => $after) {
            if ($attribute === 'updated_at') {
                continue;
            }

            if (array_key_exists($attribute, $original) && $original[$attribute] !== $after) {
                $changes[$attribute] = [
                    'before' => $this->auditValue($original[$attribute]),
                    'after' => $this->auditValue($after),
                ];
            }
        }

        return $changes;
    }

    private function auditValues(array $values): array
    {
        $auditValues = [];

        foreach ($values as $attribute => $value) {
            $auditValues[$attribute] = $this->auditValue($value);
        }

        return $auditValues;
    }

    private function auditValue(mixed $value): mixed
    {
        return $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d H:i:s.u')
            : $value;
    }

    private function schoolRequests(): Builder
    {
        return ProcurementRequest::query()->where('school_id', auth()->user()->school_id);
    }

    private function authorizeSchool(): void
    {
        abort_unless(auth()->user()?->isSchool() && auth()->user()->school_id, 403);
    }
}
