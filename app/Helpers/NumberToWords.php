<?php

namespace App\Helpers;

class NumberToWords
{
    private static $ones = [
        '',
        'One',
        'Two',
        'Three',
        'Four',
        'Five',
        'Six',
        'Seven',
        'Eight',
        'Nine',
        'Ten',
        'Eleven',
        'Twelve',
        'Thirteen',
        'Fourteen',
        'Fifteen',
        'Sixteen',
        'Seventeen',
        'Eighteen',
        'Nineteen'
    ];

    private static $tens = [
        '',
        '',
        'Twenty',
        'Thirty',
        'Forty',
        'Fifty',
        'Sixty',
        'Seventy',
        'Eighty',
        'Ninety'
    ];

    public static function convert($number)
    {
        if ($number == 0) {
            return 'Zero';
        }

        $number = number_format($number, 2, '.', '');
        $parts = explode('.', $number);
        $rupees = (int) $parts[0];
        $paise = (int) $parts[1];

        $result = '';

        if ($rupees > 0) {
            $result .= self::convertToWords($rupees);
            if ($paise > 0) {
                $result .= ' and ' . self::convertToWords($paise) . ' Paise';
            }
        } else if ($paise > 0) {
            $result .= self::convertToWords($paise) . ' Paise';
        }

        return $result;
    }

    private static function convertToWords($number)
    {
        if ($number < 20) {
            return self::$ones[$number];
        }

        if ($number < 100) {
            return self::$tens[intval($number / 10)] . ' ' . self::$ones[$number % 10];
        }

        if ($number < 1000) {
            return self::$ones[intval($number / 100)] . ' Hundred ' . self::convertToWords($number % 100);
        }

        if ($number < 100000) {
            return self::convertToWords(intval($number / 1000)) . ' Thousand ' . self::convertToWords($number % 1000);
        }

        if ($number < 10000000) {
            return self::convertToWords(intval($number / 100000)) . ' Lakh ' . self::convertToWords($number % 100000);
        }

        return self::convertToWords(intval($number / 10000000)) . ' Crore ' . self::convertToWords($number % 10000000);
    }
}
