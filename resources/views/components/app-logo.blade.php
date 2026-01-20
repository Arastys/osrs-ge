@props([
    'sidebar' => false,
])

<a {{ $attributes->merge(['class' => 'flex items-center gap-2 group']) }}>
    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-orange-500 shadow-lg shadow-orange-500/20 group-hover:scale-110 transition-transform">
        <flux:icon name="banknotes" class="w-5 h-5 text-white" />
    </div>
    <span class="font-bold text-xl tracking-tight text-zinc-900 dark:text-white">
        OSRS <span class="text-orange-500">GE</span> Tracker
    </span>
</a>
