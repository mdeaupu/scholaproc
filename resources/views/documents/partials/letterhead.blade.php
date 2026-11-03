<header class="letterhead">
    <img class="letterhead-image" src="{{ $letterhead['src'] }}" alt="Kop surat {{ $subsidiary['name'] }}">
    <div class="letterhead-identity">
        <strong>{{ $subsidiary['name'] }}</strong><br>
        NPWP: {{ $subsidiary['npwp'] }}<br>
        Direktur: {{ $subsidiary['director_name'] }}
        @if (! empty($subsidiary['address']))
            <br>{{ $subsidiary['address'] }}
        @endif
    </div>
</header>
