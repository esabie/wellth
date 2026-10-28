<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize Ghana-style numbers to digits starting with country code 233 when possible.
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '233'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Format a phone number for Ghana SMS gateways (local 0XXXXXXXXX when possible).
     */
    public static function forSms(string $phone): string
    {
        $normalized = self::normalize($phone);

        if (str_starts_with($normalized, '233') && strlen($normalized) === 12) {
            return '0'.substr($normalized, 3);
        }

        return $normalized;
    }
}
