<?php

use Livewire\Component;
use App\Models\ItemAlert;
use App\Services\OsrsGeService;
use Livewire\Attributes\On;

new class extends Component {
    #[On('alert-created')]
    #[On('portfolio-updated')]
    public function refresh()
    {
    }

    public function deleteAlert($alertId)
    {
        auth()->user()->itemAlerts()->findOrFail($alertId)->delete();
    }

    public function deletePortfolio($portfolioId)
    {
        auth()->user()->portfolios()->findOrFail($portfolioId)->delete();
    }

    public function toggleAlert($alertId)
    {
        $alert = auth()->user()->itemAlerts()->findOrFail($alertId);
        $alert->update(['is_active' => !$alert->is_active]);
    }

    public function with(OsrsGeService $geService)
    {
        $alerts = auth()->user()->itemAlerts()->with('item')->latest()->get();
        $portfolios = auth()->user()->portfolios()->with('item')->latest()->get();
        $logs = auth()->user()->alertLogs()->with('alert.item')->latest()->limit(10)->get();

        // Fetch all current prices in one go for efficiency
        $latestPrices = $geService->fetchLatestPrices();

        $alertsWithPrices = $alerts->map(function ($alert) use ($latestPrices) {
            $priceData = $latestPrices[$alert->item_id] ?? null;
            $alert->live_price = $priceData ? $priceData['high'] : null;
            return $alert;
        });

        $portfolioWithPrices = $portfolios->map(function ($p) use ($latestPrices) {
            $priceData = $latestPrices[$p->item_id] ?? null;
            $currentPrice = $priceData ? ($priceData['high'] ?? $priceData['low'] ?? 0) : 0;
            $p->current_price = $currentPrice;
            $p->total_cost = $p->buy_price * $p->quantity;
            $p->total_value = $currentPrice * $p->quantity;
            $p->profit = $p->total_value - $p->total_cost;
            $p->profit_margin = $p->total_cost > 0 ? ($p->profit / $p->total_cost) * 100 : 0;
            return $p;
        });

        return [
            'alerts' => $alertsWithPrices,
            'portfolio' => $portfolioWithPrices,
            'logs' => $logs,
            'totalPortfolioValue' => $portfolioWithPrices->sum('total_value'),
            'totalPortfolioProfit' => $portfolioWithPrices->sum('profit'),
        ];
    }
};
?>

<div class="space-y-6">
    <!-- My Investment Portfolio -->
    <div
        class="p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden transition-all hover:shadow-md hover:border-orange-500/30">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-500/10 rounded-lg">
                    <flux:icon name="briefcase" class="w-5 h-5 text-orange-500" />
                </div>
                <div>
                    <flux:heading size="lg">My Investment Portfolio</flux:heading>
                    <flux:subheading>Track your active merchanting flips</flux:subheading>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="text-right">
                    <flux:heading size="sm" class="font-mono text-orange-600 dark:text-orange-400">
                        {{ number_format($totalPortfolioValue) }} gp
                    </flux:heading>
                    <flux:text size="xs" color="{{ $totalPortfolioProfit >= 0 ? 'green' : 'red' }}" class="font-bold">
                        {{ $totalPortfolioProfit >= 0 ? '+' : '' }}{{ number_format($totalPortfolioProfit) }}
                        ({{ count($portfolio) > 0 ? number_format(($totalPortfolioProfit / max(1, $portfolio->sum('total_cost'))) * 100, 1) : 0 }}%)
                    </flux:text>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto -mx-6 text-sm">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-zinc-50 dark:bg-zinc-950 text-zinc-500 border-y border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Item</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Investment</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Live Price</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Profit/Loss</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($portfolio as $p)
                        <tr class="group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 flex items-center justify-center p-1 bg-white dark:bg-zinc-950 border border-zinc-100 dark:border-zinc-800 rounded-lg shadow-sm">
                                        <img src="{{ $p->item->icon }}" class="w-8 h-8 object-contain"
                                            alt="{{ $p->item->name }}">
                                    </div>
                                    <div>
                                        <div class="font-bold text-zinc-900 dark:text-zinc-100">{{ $p->item->name }}</div>
                                        <div class="text-[0.65rem] text-zinc-500">Qty: {{ number_format($p->quantity) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[0.65rem] text-zinc-500">Avg: {{ number_format($p->buy_price) }}</div>
                                <div class="font-medium">Total: {{ number_format($p->total_cost) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-orange-500">{{ number_format($p->current_price) }} gp</div>
                                <div class="text-[0.65rem] text-zinc-500">Current Val: {{ number_format($p->total_value) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold {{ $p->profit >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                    {{ $p->profit >= 0 ? '+' : '' }}{{ number_format($p->profit) }} gp
                                </div>
                                <div class="text-[0.65rem] {{ $p->profit >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                                    {{ $p->profit >= 0 ? '+' : '' }}{{ number_format($p->profit_margin, 1) }}%
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <flux:button variant="ghost" icon="trash" size="sm"
                                    class="text-zinc-400 hover:text-red-500 transition-colors" title="Delete Investment"
                                    wire:click="deletePortfolio({{ $p->id }})"
                                    wire:confirm="Are you sure you want to remove this investment?" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12 text-zinc-500">
                                <div class="flex flex-col items-center gap-2 opacity-50">
                                    <flux:icon name="briefcase" class="w-8 h-8" />
                                    <p>No active investments. Add items to track your profit!</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div
        class="p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden transition-all hover:shadow-md hover:border-orange-500/30">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-500/10 rounded-lg">
                    <flux:icon name="bell" class="w-5 h-5 text-orange-500" />
                </div>
                <div>
                    <flux:heading size="lg">My Price Alerts</flux:heading>
                    <flux:subheading>Manage your active item tracking</flux:subheading>
                </div>
            </div>
            <flux:badge color="orange" class="font-bold tracking-tight">{{ count($alerts) }} ACTIVE</flux:badge>
        </div>

        <div class="overflow-x-auto -mx-6">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-zinc-50 dark:bg-zinc-950 text-zinc-500 border-y border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Item</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Live Price</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Threshold</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Condition</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($alerts as $alert)
                        <tr class="group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 flex items-center justify-center p-1 bg-white dark:bg-zinc-950 border border-zinc-100 dark:border-zinc-800 rounded-lg shadow-sm">
                                        <img src="{{ $alert->item->icon }}" class="w-8 h-8 object-contain"
                                            alt="{{ $alert->item->name }}">
                                    </div>
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $alert->item->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($alert->live_price)
                                    <span
                                        class="font-mono text-zinc-900 dark:text-zinc-100">{{ number_format($alert->live_price) }}
                                        gp</span>
                                @else
                                    <span class="text-zinc-400">Loading...</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-orange-600 dark:text-orange-400 font-semibold">
                                {{ number_format($alert->threshold_price) }} gp
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge size="sm" :color="$alert->direction === 'above' ? 'green' : 'blue'"
                                    class="font-bold uppercase tracking-tighter">
                                    <flux:icon
                                        :name="$alert->direction === 'above' ? 'arrow-trending-up' : 'arrow-trending-down'"
                                        class="w-3 h-3 mr-1" />
                                    {{ $alert->direction }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4">
                                <flux:switch wire:click="toggleAlert({{ $alert->id }})" :checked="$alert->is_active"
                                    color="orange" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <flux:button variant="ghost" icon="trash" size="sm"
                                    class="text-zinc-400 hover:text-red-500 transition-colors" title="Delete Alert"
                                    wire:click="deleteAlert({{ $alert->id }})"
                                    wire:confirm="Are you sure you want to delete this alert?" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-16 text-zinc-500">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon name="bell-slash" class="w-10 h-10 text-zinc-300" />
                                    <p>No active alerts. Search for an item to start tracking!</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($logs->count() > 0)
        <div
            class="p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm transition-all hover:shadow-md hover:border-green-500/30">
            <flux:heading size="lg" class="flex items-center gap-3">
                <div class="p-2 bg-green-500/10 rounded-lg">
                    <flux:icon name="arrow-trending-up" class="w-5 h-5 text-green-500" />
                </div>
                <span>Recent Triggers</span>
            </flux:heading>
            <flux:subheading>History of reached price thresholds</flux:subheading>

            <div class="mt-6 overflow-x-auto -mx-6">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-zinc-50 dark:bg-zinc-950 text-zinc-500 border-y border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Item</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Triggered At</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[0.7rem]">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach($logs as $log)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 text-zinc-900 dark:text-zinc-100">
                                        <img src="{{ $log->alert->item->icon }}" class="w-5 h-5 object-contain" alt="">
                                        <span class="font-medium">{{ $log->alert->item->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-green-600 dark:text-green-400">
                                    {{ number_format($log->triggered_price) }} gp
                                </td>
                                <td class="px-6 py-4 text-zinc-500">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>