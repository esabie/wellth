<?php

use App\Support\PaystackServiceFee;

test('service fee grosses the payable amount up so the paystack percentage is covered', function () {
    $fee = app(PaystackServiceFee::class)->quote('200.00');

    expect($fee['service_fee_amount'])->toBe('3.98')
        ->and($fee['total'])->toBe('203.98');

    $chargedPesewas = (int) bcmul($fee['total'], '100', 0);
    $paystackFeePesewas = intdiv($chargedPesewas * 195 + 5000, 10000);

    expect($chargedPesewas - $paystackFeePesewas)->toBeGreaterThanOrEqual(20000);
});

test('service fee is zero when nothing is payable', function () {
    $fee = app(PaystackServiceFee::class)->quote('0.00');

    expect($fee['service_fee_amount'])->toBe('0.00')
        ->and($fee['total'])->toBe('0.00');
});

test('service fee is zero when the paystack percentage is zero', function () {
    config(['services.paystack.fee_percent' => '0']);

    $fee = app(PaystackServiceFee::class)->quote('200.00');

    expect($fee['service_fee_amount'])->toBe('0.00')
        ->and($fee['total'])->toBe('200.00');
});
