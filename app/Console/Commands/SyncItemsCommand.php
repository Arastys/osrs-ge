<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ge:sync-items';

    protected $description = 'Sync item metadata from OSRS Wiki API';

    public function handle(\App\Services\OsrsGeService $geService)
    {
        $this->info('Fetching item mapping...');
        $items = $geService->fetchMapping();

        if (empty($items)) {
            $this->error('No items found or failed to fetch.');
            return 1;
        }

        $this->info('Syncing ' . count($items) . ' items...');

        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        foreach (array_chunk($items, 500) as $chunk) {
            foreach ($chunk as $itemData) {
                \App\Models\Item::updateOrCreate(
                    ['id' => $itemData['id']],
                    [
                        'name' => $itemData['name'],
                        'examine' => $itemData['examine'] ?? null,
                        'icon' => $itemData['icon'] ?? null,
                        'members' => $itemData['members'] ?? false,
                        'limit' => $itemData['limit'] ?? null,
                        'high_alch' => $itemData['highalch'] ?? null,
                        'last_synced_at' => now(),
                    ]
                );
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Item sync completed.');
        return 0;
    }
}
