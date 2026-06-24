<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSetting extends Model
{
    protected $fillable = [
        'school_id',
        'kop_pusat',
        'kop_provinsi',
        'kop_sub_wilayah',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
