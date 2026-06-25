<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSetting extends Model
{
    use HasFactory;

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

    public function getLetterHead(): array
    {
        return [
            'pusat' => strtoupper($this->kop_pusat),
            'provinsi' => strtoupper($this->kop_provinsi),
            'sub_wilayah' => $this->kop_sub_wilayah ? strtoupper($this->kop_sub_wilayah) : null,
            'sekolah' => strtoupper($this->school->name),
            'alamat_lengkap' => $this->school->address . ' Kode Pos: ' . $this->school->postal_code,
            'kontak' => 'Telp: ' . $this->school->phone_number . ' | Email: ' . $this->school->email
        ];
    }
}
