<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OSRS GE Item Tracker</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-zinc-50 text-zinc-900 antialiased">
    <div class="relative min-h-screen flex flex-col items-center justify-center p-6 text-center overflow-hidden">
        <div class="absolute inset-0 pointer-events-none opacity-20">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-orange-500 rounded-full blur-[120px]"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-blue-500 rounded-full blur-[120px]">
            </div>
        </div>

        <main class="relative z-10 max-w-2xl w-full space-y-8">
            <div class="space-y-4">
                <h1 class="text-5xl font-bold tracking-tight lg:text-6xl text-zinc-900">
                    OSRS GE <span class="text-orange-500">Item Tracker</span>
                </h1>
                <p class="text-xl text-zinc-600">
                    Track Grand Exchange prices in real-time. Set alerts, receive notifications, and never miss a flip.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="w-full sm:w-auto px-8 py-4 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl transition-all shadow-lg shadow-orange-500/20">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="w-full sm:w-auto px-8 py-4 bg-zinc-900 text-white font-semibold rounded-xl transition-all hover:scale-105">
                        Log in to Start Tracking
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="w-full sm:w-auto px-8 py-4 bg-white border border-zinc-200 font-semibold rounded-xl transition-all hover:bg-zinc-50 whitespace-nowrap">
                            Create Free Account
                        </a>
                    @endif
                @endauth
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-12">
                <div class="p-4 rounded-2xl bg-white/50 backdrop-blur-sm border border-zinc-200/50">
                    <h3 class="font-semibold text-zinc-900 mb-1">Real-time Prices</h3>
                    <p class="text-sm text-zinc-500">Live data synced from the OSRS Wiki API.</p>
                </div>
                <div class="p-4 rounded-2xl bg-white/50 backdrop-blur-sm border border-zinc-200/50">
                    <h3 class="font-semibold text-zinc-900 mb-1">Custom Alerts</h3>
                    <p class="text-sm text-zinc-500">Set thresholds and get alerted immediately.</p>
                </div>
                <div class="p-4 rounded-2xl bg-white/50 backdrop-blur-sm border border-zinc-200/50">
                    <h3 class="font-semibold text-zinc-900 mb-1">Webhooks</h3>
                    <p class="text-sm text-zinc-500">Connect Discord, Slack, or any other app.</p>
                </div>
            </div>
        </main>

        <footer class="mt-auto py-8 text-sm text-zinc-500">
            &copy; {{ date('Y') }} OSRS GE Item Tracker. Data provided by the OSRS Wiki.
        </footer>
    </div>
</body>

</html>