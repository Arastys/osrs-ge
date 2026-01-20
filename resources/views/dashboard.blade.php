<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <livewire:pages::item-search />
            <livewire:pages::user-dashboard />
        </div>
    </div>
</x-layouts::app>