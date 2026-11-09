<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('visitors from russia are blocked via cloudflare country header', function () {
    $this->withHeader('CF-IPCountry', 'RU')
        ->get(route('privacy'))
        ->assertForbidden();
});

test('visitors from ghana are allowed via cloudflare country header', function () {
    $this->withHeader('CF-IPCountry', 'GH')
        ->get(route('privacy'))
        ->assertOk();
});

test('visitors from russia are blocked via geoip lookup', function () {
    Http::fake([
        'ip-api.com/*' => Http::response([
            'status' => 'success',
            'countryCode' => 'RU',
        ]),
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '5.255.255.5'])
        ->get(route('privacy'))
        ->assertForbidden();
});

test('geoip failures fail open so the shop stays available', function () {
    Http::fake([
        'ip-api.com/*' => Http::response('error', 500),
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->get(route('privacy'))
        ->assertOk();
});
