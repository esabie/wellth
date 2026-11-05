<?php

use App\Support\VersionedAsset;

test('versioned asset appends filemtime query for existing public files', function () {
    $path = 'images/logo.png';
    $fullPath = public_path($path);

    expect(is_file($fullPath))->toBeTrue();

    $url = VersionedAsset::url($path);

    expect($url)
        ->toContain(asset($path))
        ->toContain('?v='.filemtime($fullPath));
});

test('versioned asset returns plain asset url when file is missing', function () {
    $path = 'images/does-not-exist-'.uniqid('', true).'.png';

    expect(VersionedAsset::url($path))->toBe(asset($path));
});
