@if (!empty($placeDate))
    <p class="place-date">{{ $placeDate }}</p>
@endif

<table class="signature-table">
    <tr>
        <td>
            @if (!empty($left['title']))
                <p>{{ $left['title'] }}</p>
            @endif
            <div class="signature-space"></div>
            <p class="signature-name">{{ $left['name'] ?? '' }}</p>
            @if (!empty($left['subtitle']))
                <p>{{ $left['subtitle'] }}</p>
            @endif
        </td>
        <td>
            @if (!empty($right['title']))
                <p>{{ $right['title'] }}</p>
            @endif
            <div class="signature-space"></div>
            <p class="signature-name">{{ $right['name'] ?? '' }}</p>
            @if (!empty($right['subtitle']))
                <p>{{ $right['subtitle'] }}</p>
            @endif
        </td>
    </tr>
</table>

@if (!empty($center))
    <table class="signature-table" style="margin-top: 20px;">
        <tr>
            <td style="width: 100%;">
                @if (!empty($center['title']))
                    <p>{{ $center['title'] }}</p>
                @endif
                <div class="signature-space"></div>
                <p class="signature-name">{{ $center['name'] ?? '' }}</p>
                @if (!empty($center['subtitle']))
                    <p>{{ $center['subtitle'] }}</p>
                @endif
            </td>
        </tr>
    </table>
@endif
