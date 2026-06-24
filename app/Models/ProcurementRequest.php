<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'school_id',
        'supplier_id',
        'status',
        'package_category',
        'budget_year',
        'funding_source',
        'start_date',
        'end_date',
        'work_duration_text',
        'is_taxable',
        'ppn_rate',
        'pph_22_rate',
        'pph_23_rate',
        'cv_notes',
        'requested_at',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'ppn_rate' => 'decimal:2',
            'pph_22_rate' => 'decimal:2',
            'pph_23_rate' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'requested_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(ProcurementSignatory::class);
    }

    public function officialDocuments(): HasMany
    {
        return $this->hasMany(ProcurementDocument::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementRequestItem::class)->orderBy('line_number');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProcurementRequestHistory::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
