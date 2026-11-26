<?php

namespace App\Support;

use InvalidArgumentException;

class PaystackServiceFee
{
    /**
     * Gross the payable amount up so Paystack's percentage leaves that amount intact.
     *
     * Paystack deducts its fee from the amount charged, including the fee itself.
     *
     * @return array{service_fee_amount: string, total: string}
     */
    public function quote(string $payable): array
    {
        $payable = bcadd($payable, '0', 2);

        if (bccomp($payable, '0.00', 2) !== 1) {
            return [
                'service_fee_amount' => '0.00',
                'total' => '0.00',
            ];
        }

        $basisPoints = $this->basisPoints();

        if ($basisPoints === 0) {
            return [
                'service_fee_amount' => '0.00',
                'total' => $payable,
            ];
        }

        $basePesewas = (int) bcmul($payable, '100', 0);
        $chargedPesewas = $this->chargedPesewas($basePesewas, $basisPoints);

        return [
            'service_fee_amount' => bcdiv((string) ($chargedPesewas - $basePesewas), '100', 2),
            'total' => bcdiv((string) $chargedPesewas, '100', 2),
        ];
    }

    private function basisPoints(): int
    {
        $basisPoints = (int) bcmul((string) config('services.paystack.fee_percent', '1.95'), '100', 0);

        if ($basisPoints < 0 || $basisPoints >= 10_000) {
            throw new InvalidArgumentException('Paystack fee percent must be between 0 and 100.');
        }

        return $basisPoints;
    }

    /**
     * Smallest charge whose rounded Paystack fee still leaves the original pesewas.
     */
    private function chargedPesewas(int $basePesewas, int $basisPoints): int
    {
        $denominator = 10_000 - $basisPoints;
        $chargedPesewas = intdiv($basePesewas * 10_000 + $denominator - 1, $denominator);

        while ($chargedPesewas - $this->feePesewas($chargedPesewas, $basisPoints) < $basePesewas) {
            $chargedPesewas++;
        }

        return $chargedPesewas;
    }

    /**
     * Paystack fee in pesewas, rounded half up.
     */
    private function feePesewas(int $chargedPesewas, int $basisPoints): int
    {
        return intdiv($chargedPesewas * $basisPoints + 5_000, 10_000);
    }
}
