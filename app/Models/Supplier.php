<?php

namespace App\Models;

use App\Enums\DocumentType;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'company_name',
        'pic_name',
        'email',
        'phone',
        'address',
        'npwp',
        'nib',
        'director_name',
        'director_nik',
        'director_npwp',
        'director_phone',
        'commissioner_name',
        'commissioner_nik',
    ];

    protected $casts = [
    ];

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(SupplierLegalDocument::class);
    }

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class);
    }

    public function activeBusinessPermit(): ?SupplierLegalDocument
    {
        return $this->legalDocuments()
            ->where('document_type', DocumentType::BUSINESS_PERMIT)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->first();
    }

    public function hasCompleteLegalDocuments(): bool
    {
        $uploadedTypes = $this->legalDocuments->pluck('document_type');

        $hasDeed = $uploadedTypes->contains(DocumentType::DEED);
        $hasPermit = $uploadedTypes->contains(DocumentType::BUSINESS_PERMIT);

        return $hasDeed && $hasPermit;
    }

    public function totalProjects(): int
    {
        return $this->procurementRequests()->count();
    }

    public function totalProjectValue(): float|int
    {
        try {
            $procurements = $this->procurementRequests()->with('items')->get();

            $total = $procurements->sum(function ($procurement) {
                return $procurement->items->sum(function ($item) {
                    $price = $item->official_price ?? $item->estimated_price;
                    return $price * $item->quantity;
                });
            });

            return $total;
        } catch (Exception $e) {
            return 0;
        }
    }

    protected static function booted()
    {
        static::deleting(function ($supplier) {
            $supplier->legalDocuments()->delete();
        });
    }
}
