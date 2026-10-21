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
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <livewire:layout.navigation />

        <x-mary-main :with-nav="true" :full-width="true">
            <x-slot:sidebar drawer="sidebar" class="!h-dvh lg:!h-[calc(100vh-65px)] bg-base-100 border-e border-base-content/10">
                <livewire:layout.sidebar-menu />
            </x-slot:sidebar>

            <x-slot:content>
                <x-mary-toast />

                @if (isset($header))
                    <header class="bg-base-100 border-b border-base-content/10 shadow-sm -mx-5 -mt-5 mb-5 lg:-mx-10 lg:-mt-5">
                        <div class="py-4 px-4 sm:px-6 lg:px-10">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                {{ $slot }}
            </x-slot:content>
        </x-mary-main>
    </body>
</html>
