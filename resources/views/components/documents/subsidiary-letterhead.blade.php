@props([
    'name',
    'npwp' => null,
    'address' => null,
    'phone' => null,
    'email' => null,
])

<div class="w-full border-t-4 border-[#0046FF] bg-white px-5 py-4 text-gray-900">
    <p class="text-center text-lg font-extrabold uppercase tracking-wide">{{ $name }}</p>
    @if ($address)
        <p class="mt-1 text-center text-xs">{{ $address }}</p>
    @endif
    <div class="mt-2 flex flex-wrap justify-center gap-x-4 gap-y-1 text-[11px] text-gray-600">
        @if ($phone)<span>{{ $phone }}</span>@endif
        @if ($email)<span>{{ $email }}</span>@endif
        @if ($npwp)<span>NPWP {{ $npwp }}</span>@endif
    </div>
    <div class="mt-3 border-b-2 border-gray-900"></div>
</div>
