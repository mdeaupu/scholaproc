<header class="letterhead">
    <img class="letterhead-image"
        src="data:{{ data_get($snapshot, 'subsidiary.letterhead_mime_type') }};base64,{{ data_get($snapshot, 'subsidiary.letterhead_base64') }}"
        alt="Kop surat {{ data_get($snapshot, 'subsidiary.name', '-') }}">
    <div class="letterhead-identity">
        <strong>{{ data_get($snapshot, 'subsidiary.name', '-') }}</strong><br>
        NPWP: {{ data_get($snapshot, 'subsidiary.npwp', '-') }}<br>
        Direktur: {{ data_get($snapshot, 'subsidiary.director_name', '-') }}
        @if (data_get($snapshot, 'subsidiary.address'))
            <br>{{ data_get($snapshot, 'subsidiary.address') }}
        @endif
    </div>
</header>
