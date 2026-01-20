<?php

uses(Tests\TestCase::class);

use App\Services\OsrsGeService;
use Illuminate\Support\Facades\Http;

test('osrs ge service can fetch mapping', function () {
    Http::fake([
        '*/mapping' => Http::response([
            ['id' => 2, 'name' => 'Cannonball', 'examine' => 'Great for cannons.', 'icon' => 'cb.png'],
        ])
    ]);

    $service = new OsrsGeService();
    $mapping = $service->fetchMapping();

    expect($mapping)->toHaveCount(1);
    expect($mapping[0]['name'])->toBe('Cannonball');
});

test('osrs ge service can fetch latest prices', function () {
    Http::fake([
        '*/latest' => Http::response([
            'data' => [
                '2' => ['high' => 150, 'low' => 145],
            ]
        ])
    ]);

    $service = new OsrsGeService();
    $prices = $service->fetchLatestPrices();

    expect($prices)->toHaveKey('2');
    expect($prices['2']['high'])->toBe(150);
});
