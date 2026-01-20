<?php

use App\Models\Item;
use App\Models\ItemAlert;
use App\Models\User;
use App\Notifications\PriceThresholdReached;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

test('check alerts command triggers notification when price is above threshold', function () {
    Notification::fake();

    $user = User::factory()->create();
    $item = Item::create([
        'id' => 2,
        'name' => 'Cannonball',
        'examine' => 'Great for cannons.',
    ]);

    ItemAlert::create([
        'user_id' => $user->id,
        'item_id' => $item->id,
        'threshold_price' => 200,
        'direction' => 'above',
    ]);

    Http::fake([
        '*/latest' => Http::response([
            'data' => [
                '2' => ['high' => 250, 'low' => 240],
            ]
        ])
    ]);

    $this->artisan('ge:check-alerts')->assertExitCode(0);

    Notification::assertSentTo(
        $user,
        PriceThresholdReached::class,
        fn($notification) => $notification->triggeredPrice === 250
    );
});

test('check alerts command does not trigger when price is below threshold for above direction', function () {
    Notification::fake();

    $user = User::factory()->create();
    $item = Item::create([
        'id' => 2,
        'name' => 'Cannonball',
    ]);

    ItemAlert::create([
        'user_id' => $user->id,
        'item_id' => $item->id,
        'threshold_price' => 300,
        'direction' => 'above',
    ]);

    Http::fake([
        '*/latest' => Http::response([
            'data' => [
                '2' => ['high' => 250, 'low' => 240],
            ]
        ])
    ]);

    $this->artisan('ge:check-alerts')->assertExitCode(0);

    Notification::assertNotSentTo($user, PriceThresholdReached::class);
});
