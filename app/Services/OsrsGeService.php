<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OsrsGeService
{
    protected string $baseUrl = 'https://prices.runescape.wiki/api/v1/osrs';
    protected string $userAgent = 'OSRS GE Item Tracker - @tburr';

    /**
     * Fetch all item mappings from the OSRS Wiki API.
     */
    public function fetchMapping(): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->get("{$this->baseUrl}/mapping");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch OSRS item mapping', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception while fetching OSRS item mapping', [
                'message' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Fetch latest prices for all items.
     */
    public function fetchLatestPrices(): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->get("{$this->baseUrl}/latest");

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            Log::error('Failed to fetch latest OSRS prices', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception while fetching latest OSRS prices', [
                'message' => $e->getMessage(),
            ]);
        }
        return [];
    }

    /**
     * Get the full URL for an item icon from the OSRS Wiki.
     */
    public function getItemIconUrl(?string $iconName): ?string
    {
        if (!$iconName) {
            return null;
        }

        $formattedName = str_replace(' ', '_', $iconName);
        return "https://oldschool.runescape.wiki/images/{$formattedName}";
    }

    /**
     * Fetch latest price for a specific item.
     */
    public function fetchLatestPriceForItem(int $itemId): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->get("{$this->baseUrl}/latest", [
                        'id' => $itemId,
                    ]);

            if ($response->successful()) {
                return $response->json()['data'][$itemId] ?? [];
            }
        } catch (\Exception $e) {
            Log::error("Exception while fetching price for item {$itemId}", [
                'message' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Fetch historical timeseries data for an item.
     * Intervals: 5m, 1h, 6h, 24h
     */
    public function fetchTimeseries(int $itemId, string $interval = '1h'): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->get("{$this->baseUrl}/timeseries", [
                        'timestep' => $interval,
                        'id' => $itemId,
                    ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error("Exception while fetching timeseries for item {$itemId}", [
                'message' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Fetch 1-hour averages and volumes for all items.
     */
    public function fetch1hData(): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->get("{$this->baseUrl}/1h");

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error("Exception while fetching 1h OSRS data", [
                'message' => $e->getMessage(),
            ]);
        }
        return [];
    }
}
