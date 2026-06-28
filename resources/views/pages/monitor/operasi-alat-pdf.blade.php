@php
    use Illuminate\Support\Carbon;

    $hasCoords = $operasiAlat->latitude !== null && $operasiAlat->longitude !== null;

    $formatWaktu = function ($value) {
        if (! $value) {
            return '-';
        }

        return Carbon::parse($value)->locale('id')->isoFormat('dddd, DD/MM/YYYY HH:mm');
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Operasi Alat #{{ $operasiAlat->id }}</title>
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

        .map-wrap { margin-top: 10px; }
        .map-wrap img { width: 100%; max-width: 600px; border: 1px solid #d4d4d4; }
        .coords { margin-top: 6px; font-size: 11px; color: #404040; }

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

    <h1 class="report-title">LAPORAN OPERASI ALAT</h1>
    <hr class="rule">

    <h2 class="section">DETAIL OPERASI ALAT</h2>
    <table class="detail">
        <tr>
            <td class="label">Jenis Alat</td><td class="sep">:</td>
            <td class="value">{{ $operasiAlat->jenisAlatLabel() ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Operator</td><td class="sep">:</td>
            <td class="value">{{ $operasiAlat->operator?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Satuan Kerja</td><td class="sep">:</td>
            <td class="value">{{ $operasiAlat->satker?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Waktu Mulai</td><td class="sep">:</td>
            <td class="value">{{ $formatWaktu($operasiAlat->waktu_mulai) }}</td>
        </tr>
        <tr>
            <td class="label">Waktu Selesai</td><td class="sep">:</td>
            <td class="value">{{ $formatWaktu($operasiAlat->waktu_selesai) }}</td>
        </tr>
        <tr>
            <td class="label">Hasil</td><td class="sep">:</td>
            <td class="value">{{ $operasiAlat->hasilLabel() ?? 'Belum ditentukan' }}</td>
        </tr>
        <tr>
            <td class="label">Keterangan Lokasi</td><td class="sep">:</td>
            <td class="value">{{ $operasiAlat->lokasi_keterangan ?: ($operasiAlat->lokasi ?: '-') }}</td>
        </tr>
        @if ($operasiAlat->missionIssue)
            <tr>
                <td class="label">Terkait Masalah Misi</td><td class="sep">:</td>
                <td class="value">#{{ $operasiAlat->missionIssue->id }} — {{ $operasiAlat->missionIssue->jenis ?: '-' }}</td>
            </tr>
        @endif
    </table>

    <hr class="rule" style="margin-top: 20px;">

    <div class="block">
        <h3>Tujuan Operasi</h3>
        <p>{{ $operasiAlat->tujuan_operasi ?: '-' }}</p>
    </div>

    <hr class="rule">

    <div class="block">
        <h3>Catatan</h3>
        <p>{{ $operasiAlat->catatan ?: '-' }}</p>
    </div>

    <hr class="rule">

    <div class="block">
        <h2 class="section">LOKASI OPERASI</h2>
        @if ($mapImage)
            <div class="map-wrap">
                <img src="{{ $mapImage }}" alt="Peta Lokasi Operasi">
            </div>
            <p class="coords">Koordinat: {{ $operasiAlat->latitude }}, {{ $operasiAlat->longitude }}</p>
        @elseif ($hasCoords)
            {{-- Koordinat ada tapi gambar peta gagal dimuat: fallback teks koordinat. --}}
            <p class="coords">Koordinat: {{ $operasiAlat->latitude }}, {{ $operasiAlat->longitude }} (gambar peta tidak dapat dimuat)</p>
        @else
            <p>Koordinat tidak tersedia</p>
        @endif
    </div>

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
