<?php

namespace App\Http\Controllers;

use App\Models\OperasiAlat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OperasiAlatPdfController extends Controller
{
    /**
     * Generate and download the operasi alat log as a PDF.
     */
    public function __invoke(OperasiAlat $operasiAlat): Response
    {
        abort_unless($operasiAlat->isManageableBy(request()->user()), 403);

        // Eager load relasi yang dipakai di view PDF agar tidak N+1.
        $operasiAlat->load(['operator', 'satker', 'missionIssue']);

        $logoPath = storage_path('app/public/logo/image.png');
        $logo = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        $foto = $this->resolveFoto($operasiAlat);
        $mapImage = $this->resolveStaticMap($operasiAlat);

        $pdf = Pdf::loadView('pages.monitor.operasi-alat-pdf', [
            'operasiAlat' => $operasiAlat,
            'logo' => $logo,
            'foto' => $foto,
            'mapImage' => $mapImage,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laporan-operasi-alat-'.$operasiAlat->id.'.pdf');
    }

    /**
     * Resolve the evidence photo into something dompdf can embed.
     *
     * Returns a base64 data URI for local stored files, the raw URL for
     * external links, or null when there is no photo.
     */
    private function resolveFoto(OperasiAlat $operasiAlat): ?string
    {
        if (! $operasiAlat->foto_bukti) {
            return null;
        }

        if (str_starts_with($operasiAlat->foto_bukti, 'http://') || str_starts_with($operasiAlat->foto_bukti, 'https://')) {
            return $operasiAlat->foto_bukti;
        }

        $path = storage_path('app/public/'.$operasiAlat->foto_bukti);

        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }

    /**
     * Resolve a static map image (OpenStreetMap, tanpa API key) menjadi base64
     * data URI agar bisa di-embed dompdf.
     *
     * Mengembalikan null bila koordinat tidak ada ATAU bila request gambar gagal
     * (timeout/jaringan) — sehingga view bisa menampilkan fallback teks tanpa
     * menggagalkan keseluruhan generate PDF.
     */
    private function resolveStaticMap(OperasiAlat $operasiAlat): ?string
    {
        if ($operasiAlat->latitude === null || $operasiAlat->longitude === null) {
            return null;
        }

        try {
            return $this->buildOsmStaticMap((float) $operasiAlat->latitude, (float) $operasiAlat->longitude);
        } catch (\Throwable $e) {
            // Jaringan/tile gagal saat generate PDF — fallback ke teks koordinat di view.
            Log::warning('Static map PDF operasi alat gagal dibuat.', [
                'operasi_alat_id' => $operasiAlat->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Susun gambar peta statis dari tile OpenStreetMap resmi (tanpa API key)
     * lalu gambar marker di titik koordinat. Hasil berupa PNG base64 data URI
     * yang bisa langsung di-embed dompdf.
     *
     * @throws \RuntimeException bila ada tile yang gagal diunduh/diproses.
     */
    private function buildOsmStaticMap(float $lat, float $lng): string
    {
        $zoom = 15;
        $width = 600;
        $height = 300;
        $tileSize = 256;
        $n = 2 ** $zoom;

        // Konversi lat/lng -> koordinat piksel global (skema slippy map OSM).
        $latRad = deg2rad($lat);
        $xGlobal = (($lng + 180) / 360) * $n * $tileSize;
        $yGlobal = ((1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2) * $n * $tileSize;

        $left = $xGlobal - $width / 2;
        $top = $yGlobal - $height / 2;

        $tileXmin = (int) floor($left / $tileSize);
        $tileXmax = (int) floor(($left + $width) / $tileSize);
        $tileYmin = (int) floor($top / $tileSize);
        $tileYmax = (int) floor(($top + $height) / $tileSize);

        $canvas = imagecreatetruecolor($width, $height);
        // Latar abu muda untuk area kosong (tile tepi peta).
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 229, 231, 235));

        try {
            for ($tx = $tileXmin; $tx <= $tileXmax; $tx++) {
                for ($ty = $tileYmin; $ty <= $tileYmax; $ty++) {
                    if ($tx < 0 || $ty < 0 || $tx >= $n || $ty >= $n) {
                        continue;
                    }

                    $url = "https://tile.openstreetmap.org/{$zoom}/{$tx}/{$ty}.png";

                    // OSM tile usage policy WAJIB User-Agent yang mengidentifikasi aplikasi.
                    $response = Http::withHeaders([
                        'User-Agent' => config('app.name', 'Laravel').' - Operasi Alat PDF (Kejaksaan)',
                    ])->timeout(8)->get($url);

                    if (! $response->successful()) {
                        throw new \RuntimeException("Gagal unduh tile {$tx}/{$ty}: HTTP {$response->status()}");
                    }

                    $tile = imagecreatefromstring($response->body());
                    if ($tile === false) {
                        throw new \RuntimeException("Tile {$tx}/{$ty} bukan gambar valid.");
                    }

                    imagecopy(
                        $canvas,
                        $tile,
                        (int) round($tx * $tileSize - $left),
                        (int) round($ty * $tileSize - $top),
                        0, 0, $tileSize, $tileSize,
                    );
                    imagedestroy($tile);
                }
            }

            // Gambar marker di titik koordinat (tepat di tengah peta).
            $cx = (int) round($xGlobal - $left);
            $cy = (int) round($yGlobal - $top);
            $red = imagecolorallocate($canvas, 220, 38, 38);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledellipse($canvas, $cx, $cy, 18, 18, $red);
            imageellipse($canvas, $cx, $cy, 18, 18, $white);
            imagefilledellipse($canvas, $cx, $cy, 6, 6, $white);

            ob_start();
            imagepng($canvas);
            $png = (string) ob_get_clean();
        } finally {
            imagedestroy($canvas);
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
