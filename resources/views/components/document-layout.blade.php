<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Dokumen' }}</title>
    <style>
        @page {
            margin: 2cm 2cm 1.8cm 2cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.45;
        }

        h1, h2, h3, p {
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .doc-number {
            text-align: center;
            font-size: 11px;
            margin-top: 2px;
        }

        .section-spacer {
            height: 14px;
        }

        .info-table td {
            vertical-align: top;
            padding: 1px 0;
        }

        .info-table td.label {
            width: 220px;
        }

        .info-table td.colon {
            width: 14px;
        }

        .items-table {
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #333;
            padding: 5px 6px;
            font-size: 10.5px;
            vertical-align: middle;
        }

        .items-table th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        .bold        { font-weight: bold; }
        .italic      { font-style: italic; }
        .uppercase   { text-transform: uppercase; }

        .totals-row td {
            font-weight: bold;
        }

        .signature-table {
            width: 100%;
            margin-top: 26px;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }

        .signature-space {
            height: 55px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .place-date {
            text-align: right;
            margin-top: 6px;
            margin-bottom: 6px;
        }

        .note {
            margin-top: 10px;
            text-align: justify;
        }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
