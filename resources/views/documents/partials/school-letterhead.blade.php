<header class="letterhead">
    <div class="letterhead-identity">
        @if (data_get($snapshot, 'school.letterhead.pusat'))
            <strong>{{ data_get($snapshot, 'school.letterhead.pusat') }}</strong><br>
        @endif
        @if (data_get($snapshot, 'school.letterhead.provinsi'))
            <strong>{{ data_get($snapshot, 'school.letterhead.provinsi') }}</strong><br>
        @endif
        @if (data_get($snapshot, 'school.letterhead.sub_wilayah'))
            <strong>{{ data_get($snapshot, 'school.letterhead.sub_wilayah') }}</strong><br>
        @endif
        <strong>{{ data_get($snapshot, 'school.letterhead.sekolah', data_get($snapshot, 'school.name', '-')) }}</strong><br>
        {{ data_get($snapshot, 'school.letterhead.alamat_lengkap', data_get($snapshot, 'school.address', '-')) }}<br>
        {{ data_get($snapshot, 'school.letterhead.kontak', '-') }}
    </div>
</header>
