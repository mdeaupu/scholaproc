<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Pengadaan</title>
    <style>
        @page {
            margin: 1.2cm 1.2cm 1.4cm 1.2cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            line-height: 1.35;
        }

        h1, h2, h3, p {
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .report-subtitle {
            text-align: center;
            font-size: 10px;
            margin-top: 2px;
        }

        .report-meta {
            width: 100%;
            margin-top: 8px;
            font-size: 9px;
        }

        .report-meta td {
            padding: 1px 0;
            vertical-align: top;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 12px 0 4px 0;
            border-bottom: 1px solid #1a1a1a;
            padding-bottom: 2px;
        }

        .report-table th {
            background: #f0f0f0;
            border: 1px solid #999;
            padding: 3px 4px;
            text-align: left;
            font-size: 8.5px;
        }

        .report-table td {
            border: 1px solid #bbb;
            padding: 3px 4px;
            vertical-align: top;
        }

        .report-table .num {
            text-align: right;
            white-space: nowrap;
        }

        .report-table .center {
            text-align: center;
        }

        .report-table tfoot td {
            background: #f0f0f0;
            font-weight: bold;
        }

        .spacer {
            height: 10px;
        }

        .muted {
            color: #666;
        }
    </style>
</head>

<body>
    <p class="report-title">Laporan Pengadaan Barang &amp; Jasa</p>
    <p class="report-subtitle">{{ $period_label }}</p>

    <table class="report-meta">
        <tr>
            <td style="width: 18%"><strong>Filter</strong></td>
            <td>{{ $filter_description }}</td>
        </tr>
        <tr>
            <td><strong>Dicetak Oleh</strong></td>
            <td>{{ $generated_at->translatedFormat('d F Y H:i') }} WIB · {{ $generated_by }}</td>
        </tr>
    </table>

    @include('reports.partials.summary')
    @include('reports.partials.by-school')
    @include('reports.partials.by-supplier')
    @include('reports.partials.by-status')
    @include('reports.partials.transactions')
</body>

</html>
