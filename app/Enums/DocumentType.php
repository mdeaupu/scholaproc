<?php

namespace App\Enums;

enum DocumentType: string
{
    case BUSINESS_PERMIT = 'business_permit';
    case DEED = 'deed';
    case NPWP = 'npwp';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BUSINESS_PERMIT => 'Izin Usaha (NIB/SIUP)',
            self::DEED => 'Akta Perusahaan',
            self::NPWP => 'NPWP Dokumen',
            self::OTHER => 'Lainnya',
        };
    }
}
