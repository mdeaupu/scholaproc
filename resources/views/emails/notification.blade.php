<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Pengadaan</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #0046FF; color: #fff; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 600; }
        .body { padding: 25px; }
        .message { background: #f8f9fa; border-left: 4px solid #0046FF; padding: 15px 20px; border-radius: 4px; white-space: pre-wrap; font-size: 14px; }
        .footer { background: #f8f9fa; padding: 15px 25px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-whatsapp { background: #25D366; color: #fff; }
        .badge-email { background: #0046FF; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sistem Pengadaan Barang Sekolah</h1>
        </div>
        <div class="body">
            <p style="margin-top: 0;">Yth. Pengguna,</p>

            <p>Berikut adalah notifikasi dari sistem pengadaan barang sekolah:</p>

            <div class="message">{{ $message }}</div>

            @if ($procurementRequest)
                <p style="margin-top: 20px; font-size: 13px; color: #666;">
                    <strong>ID Pengadaan:</strong> #{{ $procurementRequest->id }}<br />
                    <strong>Sekolah:</strong> {{ $procurementRequest->school->name ?? '-' }}<br />
                    <strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $procurementRequest->status)) }}
                </p>
            @endif

            @if ($documents && $documents->count() > 0)
                <div style="margin-top: 20px; padding: 15px; background: #f0f4ff; border-radius: 6px;">
                    <p style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #333;">Dokumen yang tersedia:</p>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555;">
                        @foreach ($documents as $doc)
                            <li style="margin-bottom: 4px;">
                                {{ \App\Models\GeneratedDocument::typeLabel($doc->document_type) }}
                                — <a href="{{ route('documents.download', $doc->download_token) }}" style="color: #0046FF; text-decoration: none;">Unduh</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p style="font-size: 13px; color: #666; margin-top: 20px;">
                Email ini dikirim secara otomatis oleh sistem. Mohon untuk tidak membalas email ini.
            </p>
        </div>
        <div class="footer">
            <p>Sistem Informasi Pengadaan Barang Sekolah &copy; {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>
