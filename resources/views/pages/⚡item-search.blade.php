<?php

use Livewire\Component;
use App\Models\Item;
use App\Services\OsrsGeService;
use Livewire\Attributes\Url;

new class extends Component {
    #[Url]
    public $search = '';

    public $selectedItem = null;
    public $livePrice = null;
    public $timeseries = [];
    public $chartInterval = '1h';
    public $threshold = '';
    public $direction = 'above';
    public $webhookUrl = '';
    public $buyPrice = '';
    public $quantity = '';
    public $alertType = 'price';
    public $cooldown = 60;
    public $thresholdLabel = 'Target Price (GP)';
    public $thresholdPlaceholder = 'e.g. 1000000';

    public function mount()
    {
    }

    public function selectItem($itemId, OsrsGeService $geService)
    {
        $this->selectedItem = Item::find($itemId);
        $this->search = '';

        // Fetch real-time price
        $priceData = $geService->fetchLatestPriceForItem($itemId);
        if ($priceData) {
            $this->livePrice = [
                'high' => $priceData['high'] ?? 0,
                'low' => $priceData['low'] ?? 0,
                'highTime' => $priceData['highTime'] ?? null,
                'lowTime' => $priceData['lowTime'] ?? null,
            ];
            // Default threshold and buy price to current high price
            $this->threshold = $priceData['high'] ?? '';
            $this->buyPrice = $priceData['high'] ?? '';
            $this->quantity = $this->selectedItem->limit ?? 1;
        }

        // Fetch initial timeseries
        $this->fetchTimeseries($geService);
    }

    public function changeInterval($interval, OsrsGeService $geService)
    {
        $this->chartInterval = $interval;
        $this->fetchTimeseries($geService);
        $this->dispatch('chart-updated', series: $this->getChartSeries(), interval: $this->chartInterval);
    }

    protected function fetchTimeseries(OsrsGeService $geService)
    {
        if ($this->selectedItem) {
            $this->timeseries = $geService->fetchTimeseries($this->selectedItem->id, $this->chartInterval);
        }
    }

    protected function getChartSeries()
    {
        return [
            [
                'name' => 'High',
                'data' => collect($this->timeseries)->map(fn($item) => [
                    'x' => $item['timestamp'] * 1000,
                    'y' => $item['avgHighPrice'] ?? $item['avgLowPrice'] ?? 0
                ])->toArray()
            ],
            [
                'name' => 'Low',
                'data' => collect($this->timeseries)->map(fn($item) => [
                    'x' => $item['timestamp'] * 1000,
                    'y' => $item['avgLowPrice'] ?? $item['avgHighPrice'] ?? 0
                ])->toArray()
            ]
        ];
    }

    public function updatedAlertType($value)
    {
        if (!$this->selectedItem)
            return;

        switch ($value) {
            case 'price':
                $this->threshold = $this->livePrice['high'] ?? '';
                $this->thresholdLabel = 'Target Price (GP)';
                $this->thresholdPlaceholder = 'e.g. 1000000';
                break;
            case 'percentage':
                $this->threshold = '5';
                $this->thresholdLabel = 'Target Change (%)';
                $this->thresholdPlaceholder = 'e.g. 5';
                break;
            case 'margin':
                $margin = ($this->livePrice['high'] ?? 0) - ($this->livePrice['low'] ?? 0);
                $this->threshold = $margin > 0 ? $margin : '10000';
                $this->thresholdLabel = 'Target Margin (GP)';
                $this->thresholdPlaceholder = 'e.g. 50000';
                break;
            case 'volume':
                $this->threshold = '500';
                $this->thresholdLabel = 'Target Volume';
                $this->thresholdPlaceholder = 'e.g. 1000';
                break;
        }
    }

    public function createAlert()
    {
        $this->validate([
            'threshold' => 'required|numeric|min:0.1',
            'alertType' => 'required|in:price,percentage,margin,volume',
            'direction' => 'required|in:above,below',
            'webhookUrl' => 'nullable|url',
            'cooldown' => 'required|integer|min:1',
        ]);

        auth()->user()->itemAlerts()->create([
            'item_id' => $this->selectedItem->id,
            'type' => $this->alertType,
            'threshold_price' => $this->alertType === 'price' ? $this->threshold : null,
            'baseline_price' => $this->livePrice['high'] ?? null,
            'target_value' => $this->alertType !== 'price' ? $this->threshold : null,
            'direction' => $this->direction,
            'webhook_url' => $this->webhookUrl,
            'cooldown_minutes' => $this->cooldown,
        ]);

        $this->selectedItem = null;
        $this->livePrice = null;
        $this->timeseries = [];
        $this->threshold = '';
        $this->alertType = 'price';

        $this->dispatch('alert-created');
    }

    public function addToPortfolio()
    {
        $this->validate([
            'buyPrice' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1',
        ]);

        auth()->user()->portfolios()->create([
            'item_id' => $this->selectedItem->id,
            'buy_price' => $this->buyPrice,
            'quantity' => $this->quantity,
        ]);

        $this->selectedItem = null;
        $this->buyPrice = '';
        $this->quantity = '';

        $this->dispatch('portfolio-updated');
    }

    public function with()
    {
        return [
            'items' => $this->search ? Item::where('name', 'like', "%{$this->search}%")->limit(5)->get() : [],
            'chartSeries' => $this->getChartSeries()
        ];
    }
};
?>

<div class="space-y-6">
    <div
        class="relative z-30 p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm transition-all hover:shadow-md">
        <flux:heading size="lg" class="flex items-center gap-2">
            <div class="p-2 bg-orange-500/10 rounded-lg">
                <flux:icon name="magnifying-glass" class="w-5 h-5 text-orange-500" />
            </div>
            <span>Search GE Items</span>
        </flux:heading>
        <flux:subheading>Find an item to track its price in real-time</flux:subheading>

        <div class="mt-6 space-y-4 relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search items (e.g. Twisted bow)..."
                class="w-full px-4 py-3 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 border-2 border-zinc-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100 transition-colors shadow-inner" />

            @if(count($items) > 0)
                <div
                    class="absolute left-0 right-0 z-[60] mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)] max-h-80 overflow-y-auto animate-in fade-in slide-in-from-top-2 duration-200">
                    <div class="p-2 space-y-1">
                        @foreach($items as $item)
                            <button type="button" wire:click="selectItem({{ $item->id }})"
                                class="w-full text-left flex items-center gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group">
                                <div
                                    class="p-1 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-md shadow-sm group-hover:border-orange-500/50 transition-colors">
                                    <img src="{{ $item->icon }}" class="w-8 h-8 object-contain" alt="{{ $item->name }}">
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="font-medium text-zinc-900 dark:text-zinc-100 group-hover:text-orange-500 transition-colors">{{ $item->name }}</span>
                                    <span
                                        class="text-xs text-zinc-500">{{ $item->limit ? "Limit: " . number_format($item->limit) : 'No limit' }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($selectedItem)
        <div
            class="relative z-10 p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-lg animate-in zoom-in-95 duration-200">
            <flux:button variant="ghost" icon="x-mark" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-900"
                wire:click="$set('selectedItem', null)" />

            <div class="flex items-start gap-6">
                <div
                    class="p-4 bg-zinc-50 dark:bg-zinc-950 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-inner">
                    <img src="{{ $selectedItem->icon }}" class="w-20 h-20 object-contain mx-auto"
                        alt="{{ $selectedItem->name }}">
                </div>
                <div class="flex-1">
                    <flux:heading size="xl" class="mb-1">{{ $selectedItem->name }}</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 leading-relaxed">{{ $selectedItem->examine }}</flux:text>

                    @if($livePrice)
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div
                                class="bg-green-50/50 dark:bg-green-900/10 p-3 rounded-xl border border-green-100 dark:border-green-900/20">
                                <span
                                    class="text-xs text-green-600 dark:text-green-400 font-medium block uppercase tracking-wider">Current
                                    High</span>
                                <span
                                    class="text-lg font-bold text-green-700 dark:text-green-300">{{ number_format($livePrice['high']) }}
                                    <small class="text-[0.6rem]">GP</small></span>
                            </div>
                            <div
                                class="bg-orange-50/50 dark:bg-orange-900/10 p-3 rounded-xl border border-orange-100 dark:border-orange-900/20">
                                <span
                                    class="text-xs text-orange-600 dark:text-orange-400 font-medium block uppercase tracking-wider">Current
                                    Low</span>
                                <span
                                    class="text-lg font-bold text-orange-700 dark:text-orange-300">{{ number_format($livePrice['low']) }}
                                    <small class="text-[0.6rem]">GP</small></span>
                            </div>
                        </div>

                        <!-- Price Chart -->
                        <div class="mt-8 pt-8 border-t border-zinc-100 dark:border-zinc-800" x-data="{
                                        chart: null,
                                        series: @js($chartSeries),
                                        currentInterval: @js($chartInterval),
                                        init() {
                                            this.initChart();
                                            this.$watch('series', value => this.updateChart(value, this.currentInterval));
                                            $wire.on('chart-updated', (event) => this.updateChart(event.series, event.interval));
                                        },
                                        initChart() {
                                            // Robustly destroy any existing chart on this canvas (including previous Alpine initializes)
                                            if (this.chart) {
                                                this.chart.destroy();
                                                this.chart = null;
                                            }
                                            const existingChart = Chart.getChart(this.$refs.chart);
                                            if (existingChart) {
                                                existingChart.destroy();
                                            }

                                            const ctx = this.$refs.chart.getContext('2d');
                                            this.chart = new Chart(ctx, {
                                                type: 'line',
                                                data: {
                                                    datasets: [
                                                        {
                                                            label: 'High Price',
                                                            data: this.formatData(this.series[0].data),
                                                            borderColor: '#22c55e',
                                                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                                            fill: true,
                                                            tension: 0.4,
                                                            pointRadius: 0,
                                                            borderWidth: 2
                                                        },
                                                        {
                                                            label: 'Low Price',
                                                            data: this.formatData(this.series[1].data),
                                                            borderColor: '#f97316',
                                                            backgroundColor: 'rgba(249, 115, 22, 0.1)',
                                                            fill: true,
                                                            tension: 0.4,
                                                            pointRadius: 0,
                                                            borderWidth: 2
                                                        }
                                                    ]
                                                },
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
                                                    interaction: { mode: 'index', intersect: false },
                                                    plugins: {
                                                        legend: { display: false },
                                                        tooltip: {
                                                            backgroundColor: '#18181b',
                                                            titleColor: '#71717a',
                                                            bodyColor: '#fafafa',
                                                            borderColor: '#27272a',
                                                            borderWidth: 1,
                                                            padding: 12,
                                                            callbacks: {
                                                                label: (context) => `${context.dataset.label}: ${context.parsed.y.toLocaleString()} GP`
                                                            }
                                                        }
                                                    },
                                                    scales: {
                                                        x: {
                                                            type: 'time',
                                                            time: {
                                                                unit: this.getUnit(),
                                                                displayFormats: {
                                                                    minute: 'HH:mm',
                                                                    hour: 'HH:mm',
                                                                    day: 'dd MMM'
                                                                }
                                                            },
                                                            grid: { display: false },
                                                            ticks: { color: '#71717a' }
                                                        },
                                                        y: {
                                                            grid: { color: '#27272a' },
                                                            ticks: {
                                                                color: '#71717a',
                                                                callback: (val) => val >= 1000000 ? (val/1000000).toFixed(1) + 'M' : (val >= 1000 ? (val/1000).toFixed(1) + 'k' : val)
                                                            }
                                                        }
                                                    }
                                                }
                                            });
                                        },
                                        updateChart(newSeries, newInterval) {
                                            if (newInterval) this.currentInterval = newInterval;
                                            this.series = newSeries;
                                            // Destroy and re-create to ensure clean state and correct units
                                            this.initChart();
                                        },
                                        formatData(data) {
                                            return data.map(p => ({ x: p.x, y: p.y }));
                                        },
                                        getUnit() {
                                            // 5m = Intraday (Time), others = Multi-day (Date)
                                            if (this.currentInterval === '5m') return 'minute';
                                            return 'day';
                                        }
                                    }" x-effect="series = @js($chartSeries)">
                            <div class="flex items-center justify-between mb-4">
                                <flux:heading size="sm">Price History</flux:heading>
                                <div class="flex gap-1">
                                    @foreach(['5m' => '5M', '1h' => '1H', '6h' => '6H', '24h' => '1D'] as $key => $label)
                                        <button wire:click="changeInterval('{{ $key }}')"
                                            class="px-2 py-1 text-[0.65rem] font-bold rounded-md transition-colors {{ $chartInterval === $key ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="h-[250px] relative">
                                <canvas x-ref="chart"></canvas>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-zinc-100 dark:border-zinc-800" x-data="{ activeTab: 'alert' }">
                <div class="flex p-1 bg-zinc-100 dark:bg-zinc-800/50 rounded-xl mb-6">
                    <button @click="activeTab = 'alert'"
                        :class="activeTab === 'alert' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                        class="flex-1 flex items-center justify-center gap-2 py-2 text-sm font-bold rounded-lg transition-all">
                        <flux:icon name="bell" class="w-4 h-4" />
                        Create Alert
                    </button>
                    <button @click="activeTab = 'portfolio'"
                        :class="activeTab === 'portfolio' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                        class="flex-1 flex items-center justify-center gap-2 py-2 text-sm font-bold rounded-lg transition-all">
                        <flux:icon name="briefcase" class="w-4 h-4" />
                        Add to Portfolio
                    </button>
                </div>

                <div x-show="activeTab === 'alert'" class="animate-in fade-in slide-in-from-top-2 duration-300">
                    <form wire:submit="createAlert" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:select wire:model.live="alertType" :label="__('Alert Type')">
                                <flux:select.option value="price">Price Threshold</flux:select.option>
                                <flux:select.option value="percentage">Percentage Change (%)</flux:select.option>
                                <flux:select.option value="margin">Flip Margin</flux:select.option>
                                <flux:select.option value="volume">Trade Volume</flux:select.option>
                            </flux:select>

                            <flux:select wire:model="direction" :label="__('Alert Condition')">
                                <flux:select.option value="above">
                                    @if($alertType === 'price') Price goes ABOVE @elseif($alertType === 'percentage') Price
                                    RISES by @else Value is ABOVE @endif
                                </flux:select.option>
                                <flux:select.option value="below">
                                    @if($alertType === 'price') Price goes BELOW @elseif($alertType === 'percentage') Price
                                    DROPS by @else Value is BELOW @endif
                                </flux:select.option>
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input wire:model="threshold" type="number" step="0.1" :label="$thresholdLabel"
                                placeholder="{{ $thresholdPlaceholder }}" class="py-3" />

                            <flux:input wire:model="cooldown" type="number" :label="__('Notification Cooldown (Minutes)')"
                                placeholder="e.g. 60" icon-trailing="clock" class="py-3" />
                        </div>

                        <flux:input wire:model="webhookUrl" :label="__('Discord/Slack Webhook (Optional)')"
                            placeholder="https://discord.com/api/webhooks/..." icon-trailing="bell" class="py-3" />
                        <flux:button type="submit" variant="primary"
                            class="w-full py-4 font-bold shadow-lg shadow-orange-500/10">
                            Create Advanced Price Alert
                        </flux:button>
                    </form>
                </div>

                <div x-show="activeTab === 'portfolio'" x-cloak class="animate-in fade-in slide-in-from-top-2 duration-300">
                    <form wire:submit="addToPortfolio" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input wire:model="buyPrice" type="number" :label="__('Buy Price (GP)')"
                                placeholder="e.g. 1000000" class="py-3" />
                            <flux:input wire:model="quantity" type="number" :label="__('Quantity')" placeholder="e.g. 10"
                                class="py-3" />
                        </div>
                        <flux:button type="submit" variant="primary"
                            class="w-full py-4 font-bold shadow-lg shadow-orange-500/10">
                            Add to Investment Portfolio
                        </flux:button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>