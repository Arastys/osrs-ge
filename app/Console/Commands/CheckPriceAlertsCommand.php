<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckPriceAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ge:check-alerts';

    protected $description = 'Check real-time prices against active user alerts';

    public function handle(\App\Services\OsrsGeService $geService)
    {
        $this->info('Fetching latest prices...');
        $prices = $geService->fetchLatestPrices();
        $hourlyData = [];

        if (empty($prices)) {
            $this->error('Failed to fetch latest prices.');
            return 1;
        }

        $activeAlerts = \App\Models\ItemAlert::where('is_active', true)
            ->with(['item', 'user'])
            ->get();

        $this->info('Checking ' . $activeAlerts->count() . ' active alerts...');

        foreach ($activeAlerts as $alert) {
            // Check user-defined cooldown
            if ($alert->last_notified_at && $alert->last_notified_at->addMinutes($alert->cooldown_minutes)->isFuture()) {
                continue;
            }

            $itemId = $alert->item_id;
            if (!isset($prices[$itemId])) {
                continue;
            }

            $currentHigh = $prices[$itemId]['high'] ?? 0;
            $currentLow = $prices[$itemId]['low'] ?? 0;
            $currentPrice = $currentHigh ?: $currentLow;

            if (!$currentPrice)
                continue;

            $isTriggered = false;
            $triggeredPrice = 0;

            switch ($alert->type) {
                case 'price':
                    $triggeredPrice = $currentPrice;
                    if ($alert->direction === 'above' && $currentPrice >= $alert->threshold_price) {
                        $isTriggered = true;
                    } elseif ($alert->direction === 'below' && $currentPrice <= $alert->threshold_price) {
                        $isTriggered = true;
                    }
                    break;

                case 'percentage':
                    if ($alert->baseline_price > 0) {
                        $diff = $currentPrice - $alert->baseline_price;
                        $percentChange = ($diff / $alert->baseline_price) * 100;
                        $triggeredPrice = round($percentChange, 2);

                        if ($alert->direction === 'above' && $percentChange >= $alert->target_value) {
                            $isTriggered = true;
                        } elseif ($alert->direction === 'below' && $percentChange <= -$alert->target_value) {
                            $isTriggered = true;
                        }
                    }
                    break;

                case 'margin':
                    $margin = $currentHigh - $currentLow;
                    $triggeredPrice = $margin;
                    if ($alert->direction === 'above' && $margin >= $alert->target_value) {
                        $isTriggered = true;
                    } elseif ($alert->direction === 'below' && $margin <= $alert->target_value) {
                        $isTriggered = true;
                    }
                    break;

                case 'volume':
                    // Fetch hourly data if not already fetched
                    if (empty($hourlyData)) {
                        $this->info('Fetching hourly volume data...');
                        $hourlyData = $geService->fetch1hData();
                    }

                    $itemHourly = $hourlyData[$itemId] ?? null;
                    if ($itemHourly) {
                        $totalVolume = ($itemHourly['highPriceVolume'] ?? 0) + ($itemHourly['lowPriceVolume'] ?? 0);
                        $triggeredPrice = $totalVolume;

                        if ($alert->direction === 'above' && $totalVolume >= $alert->target_value) {
                            $isTriggered = true;
                        } elseif ($alert->direction === 'below' && $totalVolume <= $alert->target_value) {
                            $isTriggered = true;
                        }
                    }
                    break;
            }

            if ($isTriggered) {
                $this->info("Triggering alert for {$alert->item->name} (Value: {$triggeredPrice})");

                $alert->user->notify(new \App\Notifications\PriceThresholdReached($alert, $triggeredPrice));

                $alert->update([
                    'last_triggered_at' => now(),
                    'last_notified_at' => now()
                ]);

                \App\Models\AlertLog::create([
                    'item_alert_id' => $alert->id,
                    'triggered_price' => $triggeredPrice,
                ]);
            }
        }

        $this->info('Alert check completed.');
        return 0;
    }
}
