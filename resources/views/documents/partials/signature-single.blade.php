<table style="margin-top: 20px;">
    <tr>
        <td style="width: 55%;"></td>
        <td style="width: 45%; text-align: center;">
            <p>{{ $placeDate }}</p>
            @if (!empty($signer['title']))
                <p>{{ $signer['title'] }}</p>
            @endif
            <div class="signature-space"></div>
            <p class="signature-name">{{ $signer['name'] ?? '' }}</p>
            @if (!empty($signer['subtitle']))
                <p>{{ $signer['subtitle'] }}</p>
            @endif
        </td>
    </tr>
</table>
