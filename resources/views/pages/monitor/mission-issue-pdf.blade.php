@php
    use Illuminate\Support\Carbon;

    $statusLabel = match ($issue->status) {
        'selesai' => 'Selesai',
        'proses' => 'Dalam Proses',
        'baru' => 'Baru',
        default => ucfirst((string) $issue->status),
    };

    $formatTanggal = function ($value) {
        if (! $value) {
            return '-';
        }

        return Carbon::parse($value)->locale('id')->isoFormat('dddd, DD/MM/YYYY');
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Misi #{{ $issue->id }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1a1a1a; font-size: 12px; line-height: 1.5; }
        .page { padding: 32px 40px; }

        .kop { width: 100%; border-bottom: 3px solid #000; padding-bottom: 12px; }
        .kop td { vertical-align: middle; }
        .kop-logo { width: 90px; text-align: center; }
        .kop-logo img { width: 78px; height: auto; }
        .kop-text { text-align: center; }
        .kop-text .line1 { font-size: 15px; font-weight: bold; }
        .kop-text .line2 { font-size: 18px; font-weight: bold; }
        .kop-text .address { font-size: 10px; margin-top: 4px; color: #333; }

        h1.report-title { font-size: 20px; font-weight: bold; margin: 24px 0 6px; }
        .rule { border: 0; border-top: 1px solid #d4d4d4; margin: 10px 0 18px; }

        h2.section { font-size: 13px; font-weight: bold; margin: 0 0 12px; letter-spacing: .3px; }

        table.detail { width: 100%; border-collapse: collapse; }
        table.detail td { padding: 6px 0; vertical-align: top; }
        table.detail .label { width: 200px; color: #404040; }
        table.detail .sep { width: 20px; }
        table.detail .value { font-weight: 600; }

        .block { margin-top: 20px; }
        .block h3 { font-size: 13px; font-weight: bold; margin: 0 0 6px; }
        .block p { margin: 0; white-space: pre-wrap; }

        .foto-wrap { margin-top: 10px; }
        .foto-wrap img { max-width: 100%; max-height: 520px; border: 1px solid #d4d4d4; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
<div class="page">
    <table class="kop">
        <tr>
            <td class="kop-logo">
                @if ($logo)
                    <img src="{{ $logo }}" alt="Logo Kejaksaan">
                @endif
            </td>
            <td class="kop-text">
                <div class="line1">KEJAKSAAN REPUBLIK INDONESIA</div>
                <div class="line2">KEJAKSAAN TINGGI DKI JAKARTA</div>
                <div class="address">
                    Jl. H. R. Rasuna Said No.2, RT.6/RW.4, Kuningan Tim., Kecamatan Setiabudi, Kota Jakarta
                    Selatan, Daerah Khusus Ibukota Jakarta 12950
                </div>
            </td>
        </tr>
    </table>

    <h1 class="report-title">PENELITIAN LAPORAN</h1>
    <hr class="rule">

    <h2 class="section">DETAIL MISI</h2>
    <table class="detail">
        <tr>
            <td class="label">Jenis Insiden</td><td class="sep">:</td>
            <td class="value">{{ $issue->jenis ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Lokasi</td><td class="sep">:</td>
            <td class="value">{{ $issue->lokasi ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Status</td><td class="sep">:</td>
            <td class="value">{{ $statusLabel }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Mulai</td><td class="sep">:</td>
            <td class="value">{{ $formatTanggal($issue->tanggal) }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Selesai</td><td class="sep">:</td>
            <td class="value">{{ $formatTanggal($issue->tanggal) }}</td>
        </tr>
        <tr>
            <td class="label">Satuan Kerja</td><td class="sep">:</td>
            <td class="value">{{ $issue->satker ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Dibuat oleh</td><td class="sep">:</td>
            <td class="value">{{ $issue->creator?->name ?? ($issue->pelapor ?: '-') }}</td>
        </tr>
        <tr>
            <td class="label">Pihak Terlibat</td><td class="sep">:</td>
            <td class="value">{{ $issue->pihak_terlibat ?: '-' }}</td>
        </tr>
    </table>

    <hr class="rule" style="margin-top: 20px;">

    <div class="block">
        <h3>Deskripsi Laporan</h3>
        <p>{{ $issue->deskripsi ?: '-' }}</p>
    </div>

    <hr class="rule">

    <div class="block">
        <h2 class="section">SARAN DAN TINDAK LANJUT</h2>
        <p>{{ $issue->tindakan ?: '-' }}</p>
    </div>

    <hr class="rule">

    @if ($foto)
        <div class="page-break"></div>
        <div class="block">
            <h3>Foto Bukti</h3>
            <div class="foto-wrap">
                <img src="{{ $foto }}" alt="Foto Bukti">
            </div>
        </div>
    @endif
</div>
</body>
</html>
