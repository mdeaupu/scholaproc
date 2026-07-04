<?php

namespace App\Helpers;

class Terbilang
{
    private static array $angka = [
        '',
        'Satu',
        'Dua',
        'Tiga',
        'Empat',
        'Lima',
        'Enam',
        'Tujuh',
        'Delapan',
        'Sembilan',
        'Sepuluh',
        'Sebelas',
    ];

    public static function make(float|int $number, bool $withRupiah = false): string
    {
        $number = (int) round($number);

        if ($number < 0) {
            return 'Minus ' . self::make(abs($number));
        }

        $result = trim(self::convert($number));
        $result = preg_replace('/\s+/', ' ', $result);

        return $withRupiah ? $result . ' Rupiah' : $result;
    }

    private static function convert(int $number): string
    {
        if ($number < 12) {
            return self::$angka[$number];
        }

        if ($number < 20) {
            return self::convert($number - 10) . ' Belas';
        }

        if ($number < 100) {
            return self::convert((int) ($number / 10)) . ' Puluh ' . self::convert($number % 10);
        }

        if ($number < 200) {
            return 'Seratus ' . self::convert($number - 100);
        }

        if ($number < 1000) {
            return self::convert((int) ($number / 100)) . ' Ratus ' . self::convert($number % 100);
        }

        if ($number < 2000) {
            return 'Seribu ' . self::convert($number - 1000);
        }

        if ($number < 1000000) {
            return self::convert((int) ($number / 1000)) . ' Ribu ' . self::convert($number % 1000);
        }

        if ($number < 1000000000) {
            return self::convert((int) ($number / 1000000)) . ' Juta ' . self::convert($number % 1000000);
        }

        if ($number < 1000000000000) {
            return self::convert((int) ($number / 1000000000)) . ' Miliar ' . self::convert($number % 1000000000);
        }

        return self::convert((int) ($number / 1000000000000)) . ' Triliun ' . self::convert($number % 1000000000000);
    }
}
