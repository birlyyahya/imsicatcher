<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves the LibreSpeed speedtest backend (download / upload / ping / getIP)
 * through real Laravel routes.
 *
 * The raw PHP files under public/speedtest/backend are not executed when the
 * app is served via Herd/Valet/nginx (requests get routed to index.php and
 * return a 404 HTML page), which broke the measurements and the IP lookup.
 * Re-implementing the tiny backend here makes it work on any web server.
 */
class SpeedtestController extends Controller
{
    /**
     * Endpoint used for ping (GET) and upload (POST) tests.
     *
     * Responds with an empty body and aggressive no-cache headers. Any uploaded
     * payload is simply discarded — only the transfer time matters.
     */
    public function empty(Request $request): Response
    {
        return response('', 200, $this->noCacheHeaders());
    }

    /**
     * Download test endpoint: streams `ckSize` megabytes of incompressible
     * random data (default 4 MB, capped at 1024 MB) as an octet-stream.
     */
    public function garbage(Request $request): StreamedResponse
    {
        $chunks = $this->chunkCount($request);

        $headers = array_merge($this->noCacheHeaders(), [
            'Content-Description' => 'File Transfer',
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename=random.dat',
            'Content-Transfer-Encoding' => 'binary',
        ]);

        return new StreamedResponse(function () use ($chunks): void {
            // One megabyte of random data reused for every chunk.
            $data = random_bytes(1048576);

            for ($i = 0; $i < $chunks; $i++) {
                echo $data;
                flush();
            }
        }, 200, $headers);
    }

    /**
     * Returns the client's IP address (with a friendly label for local/private
     * addresses) as JSON, matching the shape LibreSpeed's worker expects.
     */
    public function getIP(Request $request): Response
    {
        $ip = $this->clientIp($request);
        $label = $this->localOrPrivateLabel($ip);

        $processedString = $label !== null
            ? $ip.' - '.$label
            : $ip;

        return response()->json([
            'processedString' => $processedString,
            'rawIspInfo' => '',
        ], 200, $this->noCacheHeaders());
    }

    /**
     * @return array<string, string>
     */
    private function noCacheHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, s-maxage=0',
            'Pragma' => 'no-cache',
        ];
    }

    private function chunkCount(Request $request): int
    {
        $ckSize = $request->query('ckSize');

        if (! is_string($ckSize) || ! ctype_digit($ckSize) || (int) $ckSize <= 0) {
            return 4;
        }

        return min((int) $ckSize, 1024);
    }

    private function clientIp(Request $request): string
    {
        $ip = $request->ip() ?? '';

        return preg_replace('/^::ffff:/', '', $ip) ?? $ip;
    }

    /**
     * Human-readable label for localhost / private / link-local addresses,
     * or null for a public address.
     */
    private function localOrPrivateLabel(string $ip): ?string
    {
        if ($ip === '::1') {
            return 'localhost IPv6 access';
        }
        if (stripos($ip, 'fe80:') === 0) {
            return 'link-local IPv6 access';
        }
        if (preg_match('/^(fc|fd)([0-9a-f]{0,4}:){1,7}[0-9a-f]{1,4}$/i', $ip) === 1) {
            return 'ULA IPv6 access';
        }
        if (str_starts_with($ip, '127.')) {
            return 'localhost IPv4 access';
        }
        if (str_starts_with($ip, '10.')) {
            return 'private IPv4 access';
        }
        if (preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip) === 1) {
            return 'private IPv4 access';
        }
        if (str_starts_with($ip, '192.168.')) {
            return 'private IPv4 access';
        }
        if (str_starts_with($ip, '169.254.')) {
            return 'link-local IPv4 access';
        }

        return null;
    }
}
