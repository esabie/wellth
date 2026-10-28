<?php

test('route list accepts the columns option for tooling compatibility', function () {
    $this->artisan('route:list', [
        '--columns' => 'method,uri,name',
        '--path' => 'checkout',
        '--except-vendor' => true,
    ])->assertSuccessful();
});

test('route list can filter columns when outputting json', function () {
    $this->artisan('route:list', [
        '--columns' => 'method,uri,name',
        '--json' => true,
        '--path' => 'checkout',
        '--except-vendor' => true,
    ])->assertSuccessful();
});
