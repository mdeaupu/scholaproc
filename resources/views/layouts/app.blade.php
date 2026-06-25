<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .drawer {
            min-height: 100vh;
        }

        @media (min-width: 1024px) {
            .drawer-side {
                top: 0 !important;
                height: 100vh !important;
            }
        }
    </style>
</head>

<body class="font-sans antialiased bg-white text-black min-h-screen">
    <x-mary-nav sticky class="lg:hidden bg-white border-b border-gray-200 px-4">
        <x-slot:brand>
            <div class="flex items-center gap-2.5">
                <x-application-logo class="w-6 h-6 object-contain" />
                <span
                    class="font-bold text-md tracking-tight text-[#0046FF]">{{ config('app.name', 'Scholaproc') }}</span>
            </div>
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer"
                class="lg:hidden btn btn-ghost btn-sm btn-circle text-[#0046FF] hover:bg-[#0046FF]/10">
                <x-mary-icon name="o-bars-3" class="w-5 h-5 cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-mary-nav>
    <x-mary-main drawer="main-drawer" with-nav full-width>
        <x-slot:sidebar drawer="main-drawer"
            class="bg-white border-r border-gray-200 sticky top-0 h-screen overflow-y-auto">
            <div class="flex items-center justify-between lg:justify-start gap-3 px-6 h-16 border-b border-gray-200">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                    <x-application-logo class="w-6 h-6 object-contain" />
                    <span class="font-black text-lg tracking-tight text-black hover:text-[#0046FF] transition-colors">
                        {{ config('app.name', 'Scholaproc') }}
                    </span>
                </a>
                <label for="main-drawer"
                    class="lg:hidden btn btn-ghost btn-sm btn-circle text-[#FF8040] hover:bg-[#FF8040]/10">
                    <x-mary-icon name="o-x-mark" class="w-5 h-5" />
                </label>
            </div>
            <livewire:layout.navigation />
        </x-slot:sidebar>
        <x-slot:content class="p-6 lg:p-8 bg-white">
            <x-mary-toast />
            @if (isset($header))
                <div class="mb-6 pb-5 border-b border-[#0046FF]/20">
                    <h1 class="text-2xl font-bold tracking-tight text-black">
                        {{ $header }}
                    </h1>
                </div>
            @endif
            <main class="text-black">
                {{ $slot }}
            </main>
        </x-slot:content>
    </x-mary-main>
</body>

</html>