<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class BudgetYear extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class);
    }

    public function documentNumberSequences(): HasMany
    {
        return $this->hasMany(DocumentNumberSequence::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function activate(): void
    {
        DB::transaction(function () {
            // Nonaktifkan semua tahun anggaran lain[cite: 1]
            self::where('id', '!=', $this->id)->update(['is_active' => false]);
            // Aktifkan tahun anggaran ini[cite: 1]
            $this->update(['is_active' => true]);
        });
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active;
    }

    public function overlapsWith($startDate, $endDate, $ignoreId = null): bool
    {
        return self::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        })
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
