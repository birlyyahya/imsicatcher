<?php

namespace App\Http\Controllers;

use App\Models\MissionIssue;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class MissionIssuePdfController extends Controller
{
    /**
     * Generate and download the mission issue report as a PDF.
     */
    public function __invoke(MissionIssue $issue): Response
    {
        abort_unless($issue->isManageableBy(request()->user()), 403);

        $issue->load('creator');

        $logoPath = storage_path('app/public/logo/image.png');
        $logo = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        $foto = $this->resolveFoto($issue);

        $pdf = Pdf::loadView('pages.monitor.mission-issue-pdf', [
            'issue' => $issue,
            'logo' => $logo,
            'foto' => $foto,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laporan-misi-'.$issue->id.'.pdf');
    }

    /**
     * Resolve the evidence photo into something dompdf can embed.
     *
     * Returns a base64 data URI for local stored files, the raw URL for
     * external links, or null when there is no photo.
     */
    private function resolveFoto(MissionIssue $issue): ?string
    {
        if (! $issue->foto_bukti) {
            return null;
        }

        if (str_starts_with($issue->foto_bukti, 'http://') || str_starts_with($issue->foto_bukti, 'https://')) {
            return $issue->foto_bukti;
        }

        $path = storage_path('app/public/'.$issue->foto_bukti);

        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }
}
