<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

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
        'director_address',
        'commissioner_name',
        'commissioner_nik',
        'commissioner_address',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class);
    }
}
