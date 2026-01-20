<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OsrsGeService;
use App\Models\ItemAlert;
use App\Notifications\PriceThresholdReached;

class TestWebhookCommand extends Command
{
    protected $signature = 'ge:test-webhook {alert_id? : The ID of the alert to trigger}';

    protected $description = 'Manually trigger a price alert notification for testing';

    public function handle(OsrsGeService $geService)
    {
        $alertId = $this->argument('alert_id');

        if ($alertId) {
            $alert = ItemAlert::with('item', 'user')->findOrFail($alertId);
        } else {
            // Try to find a Twisted Bow alert as requested by user
            $alert = ItemAlert::whereHas('item', function ($query) {
                $query->where('name', 'like', '%Twisted bow%');
            })->with('item', 'user')->first();

            if (!$alert) {
                $alert = ItemAlert::with('item', 'user')->first();
            }
        }

        if (!$alert) {
            $this->error('No active alerts found to test.');
            return 1;
        }

        $this->info("Triggering test alert for {$alert->item->name} (User: {$alert->user->email})...");

        // Fetch current live price for accuracy
        $priceData = $geService->fetchLatestPriceForItem($alert->item_id);
        $price = $priceData['high'] ?? $alert->threshold_price;

        $alert->user->notify(new PriceThresholdReached($alert, $price));

        $this->info("Notification sent successfully! (Current Price: " . number_format($price) . " gp)");
        return 0;
    }
}
