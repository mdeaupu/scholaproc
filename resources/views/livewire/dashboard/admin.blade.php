<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white text-black p-6 rounded-lg shadow-sm">
                <h1 class="text-xl font-semibold">
                    Selamat Datang, {{ auth()->user()->name }}!
                </h1>
                <div class="mt-4 p-4 bg-gray-50 rounded border border-gray-100 text-sm space-y-1">
                    <p><strong>Username:</strong> {{ auth()->user()->username }}</p>
                    <p><strong>Hak Akses:</strong> <span
                            class="uppercase text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-mono">{{ auth()->user()->role }}</span>
                    </p>
                    @if(auth()->user()->school_id)
                        <p><strong>ID Sekolah:</strong> {{ auth()->user()->school_id }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>