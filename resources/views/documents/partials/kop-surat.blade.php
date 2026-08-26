@php
    $letterHead = $schoolSetting
        ? $schoolSetting->getLetterHead()
        : [
            'pusat' => '',
            'provinsi' => '',
            'sub_wilayah' => null,
            'sekolah' => strtoupper($school->name),
            'alamat_lengkap' => $school->address,
            'kontak' => 'Telp: ' . $school->phone_number,
        ];
@endphp
<table style="border-bottom: 3px solid #1a1a1a; padding-bottom: 6px;">
    <tr>
        <td style="width: 70px; vertical-align: top;">
            <img src="{{ public_path($logo ?? 'images/logo-jabar.svg') }}" style="width: 62px;" alt="Logo">
        </td>
        <td style="vertical-align: top; text-align: center;">
            <p style="font-size: 13px; font-weight: bold; text-transform: uppercase;">{{ $letterHead['pusat'] }}</p>
            <p style="font-size: 13px; font-weight: bold; text-transform: uppercase;">{{ $letterHead['provinsi'] }}</p>
            @if (!empty($letterHead['sub_wilayah']))
                <p style="font-size: 12px; font-weight: bold; text-transform: uppercase;">
                    {{ $letterHead['sub_wilayah'] }}</p>
            @endif
            <p style="font-size: 14px; font-weight: bold; text-transform: uppercase; margin-top: 2px;">
                {{ $letterHead['sekolah'] }}</p>
            <p style="font-size: 10px; margin-top: 2px;">{{ $letterHead['alamat_lengkap'] }}</p>
            <p style="font-size: 10px;">{{ $letterHead['kontak'] }}</p>
        </td>
        <td style="width: 70px;"></td>
    </tr>
</table>
