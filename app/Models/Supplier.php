<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

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

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(SupplierLegalDocument::class);
    }

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class);
    }
}
