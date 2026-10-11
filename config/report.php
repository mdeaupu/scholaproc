<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zona Waktu Laporan
    |--------------------------------------------------------------------------
    |
    | Batas periode laporan (Hari Ini, Bulan Ini, rentang kustom, dll) dihitung
    | dalam zona waktu ini, lalu dikonversi ke zona waktu penyimpanan aplikasi
    | saat dibandingkan. Ini menjaga filter periode tetap mengikuti kalender
    | WIB tanpa memaksa seluruh aplikasi (dan data historis) pindah zona waktu.
    |
    */

    'timezone' => env('REPORT_TIMEZONE', 'Asia/Jakarta'),

];
