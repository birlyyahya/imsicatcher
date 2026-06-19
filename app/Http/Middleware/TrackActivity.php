<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\MissionIssue;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $actorId = auth()->id();
        $actorName = auth()->user()?->name;

        $response = $next($request);

        if ($this->shouldSkip($request)) {
            return $response;
        }

        try {
            $payload = $this->resolveLogPayload($request, $response);

            if ($payload === null) {
                return $response;
            }

            ActivityLog::query()->create([
                'user_id' => $payload['user_id'] ?? auth()->id() ?? $actorId,
                'user_name' => $payload['user_name'] ?? auth()->user()?->name ?? $actorName,
                'action' => $payload['action'],
                'description' => $payload['description'],
                'agent' => $payload['agent'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => $payload['metadata'],
                'logged_at' => now(),
            ]);
        } catch (Throwable) {
            // Jangan sampai request utama gagal hanya karena gagal mencatat log.
        }

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return true;
        }

        return $request->is([
            'up',
            'favicon.ico',
            '_debugbar/*',
            'storage/*',
            'build/*',
        ]);
    }

    private function resolveLogPayload(Request $request, Response $response): ?array
    {
        if ($this->isLivewireUpdateRequest($request)) {
            return $this->resolveLivewirePayload($request, $response);
        }

        $method = strtoupper($request->method());
        $routeName = $request->route()?->getName();
        $path = $request->path();

        $action = match (true) {
            $routeName === 'login.store' => 'login',
            $routeName === 'logout' => 'logout',
            $routeName === 'register.store' => 'create',
            in_array($method, ['PUT', 'PATCH'], true) => 'update',
            $method === 'DELETE' => 'delete',
            default => null,
        };

        if ($action === null) {
            return null;
        }

        $agent = $routeName ?: $path;
        $description = $this->buildHttpDescription($action, $routeName, $response->getStatusCode());

        $metadata = [
            'route_name' => $routeName,
            'method' => $method,
            'path' => $path,
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
        ];

        return [
            'action' => $action,
            'description' => $description,
            'agent' => $agent,
            'metadata' => $metadata,
        ];
    }

    private function isLivewireUpdateRequest(Request $request): bool
    {
        $path = trim($request->path(), '/');
        $routeName = $request->route()?->getName();

        return $routeName === 'default-livewire.update'
            || ($request->isMethod('POST') && str_contains($path, 'livewire') && str_ends_with($path, '/update'));
    }

    private function resolveLivewirePayload(Request $request, Response $response): ?array
    {
        $components = data_get($request->all(), 'components', []);

        if (!is_array($components) || count($components) === 0) {
            return null;
        }

        foreach ($components as $componentPayload) {
            $componentName = $this->extractLivewireComponentName($componentPayload);
            $calledMethod = data_get($componentPayload, 'calls.0.method');
            $calledParams = data_get($componentPayload, 'calls.0.params', []);

            if (!is_string($calledMethod) || $calledMethod === '') {
                continue;
            }

            $action = $this->classifyLivewireAction($calledMethod, (string) $componentName);

            if ($action === null) {
                continue;
            }

            $targetHint = $this->extractTargetHint($calledParams);
            $targetHint = $targetHint !== ''
                ? $targetHint
                : $this->guessCreatedEntityId($action, $componentName, auth()->id());
            $description = $this->buildLivewireDescription(
                $action,
                $componentName,
                $calledMethod,
                $targetHint,
                $response->getStatusCode()
            );

            return [
                'action' => $action,
                'description' => $description,
                'agent' => trim(($componentName ?: 'livewire').'.'.$calledMethod, '.'),
                'metadata' => [
                    'route_name' => $request->route()?->getName(),
                    'method' => strtoupper($request->method()),
                    'path' => $request->path(),
                    'url' => $request->fullUrl(),
                    'status_code' => $response->getStatusCode(),
                    'livewire_component' => $componentName,
                    'livewire_method' => $calledMethod,
                    'livewire_params' => $calledParams,
                ],
                // Untuk aksi logout dari Livewire, user bisa sudah null setelah session invalidated.
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name,
            ];
        }

        return null;
    }

    private function guessCreatedEntityId(string $action, ?string $componentName, ?int $actorId): string
    {
        if ($action !== 'create' || $actorId === null) {
            return '';
        }

        $component = strtolower((string) $componentName);

        if (str_contains($component, 'monitor.mission-issues-create')) {
            $createdId = MissionIssue::query()
                ->where('dibuat_oleh', $actorId)
                ->where('created_at', '>=', now()->subSeconds(15))
                ->latest('id')
                ->value('id');

            return $createdId ? (string) $createdId : '';
        }

        return '';
    }

    private function extractLivewireComponentName(array $componentPayload): ?string
    {
        $componentName = data_get($componentPayload, 'snapshot.memo.name');

        if (is_string($componentName) && $componentName !== '') {
            return $componentName;
        }

        $snapshot = data_get($componentPayload, 'snapshot');

        if (is_string($snapshot) && $snapshot !== '') {
            $decodedSnapshot = json_decode($snapshot, true);
            $decodedName = data_get($decodedSnapshot, 'memo.name');

            if (is_string($decodedName) && $decodedName !== '') {
                return $decodedName;
            }
        }

        return null;
    }

    private function extractTargetHint(mixed $calledParams): string
    {
        if (!is_array($calledParams) || count($calledParams) === 0) {
            return '';
        }

        $firstParam = $calledParams[0] ?? null;

        if (is_scalar($firstParam)) {
            return (string) $firstParam;
        }

        if (is_array($firstParam)) {
            $id = data_get($firstParam, 'id');

            if (is_scalar($id)) {
                return (string) $id;
            }
        }

        return '';
    }

    private function classifyLivewireAction(string $methodName, string $componentName): ?string
    {
        $method = strtolower($methodName);
        $component = strtolower($componentName);

        if (str_contains($method, 'delete') || str_contains($method, 'destroy') || str_contains($method, 'remove')) {
            return 'delete';
        }

        if (str_contains($method, 'update') || str_contains($method, 'edit')) {
            return 'update';
        }

        if (str_contains($method, 'create') || str_contains($method, 'store') || str_contains($method, 'add')) {
            return 'create';
        }

        if (str_contains($method, 'login') || str_contains($method, 'authenticate')) {
            return 'login';
        }

        if (str_contains($method, 'logout')) {
            return 'logout';
        }

        if (str_contains($method, 'save')) {
            if (str_contains($component, 'create')) {
                return 'create';
            }

            if (str_contains($component, 'edit')) {
                return 'update';
            }

            return 'update';
        }

        return null;
    }

    private function buildHttpDescription(string $action, ?string $routeName, int $statusCode): string
    {
        return match ($action) {
            'login' => "Login ke sistem ({$statusCode})",
            'logout' => "Logout dari sistem ({$statusCode})",
            'create' => $routeName === 'register.store'
                ? "Registrasi akun baru ({$statusCode})"
                : "Membuat data melalui endpoint {$routeName} ({$statusCode})",
            'update' => "Memperbarui data melalui endpoint {$routeName} ({$statusCode})",
            'delete' => "Menghapus data melalui endpoint {$routeName} ({$statusCode})",
            default => "Aksi {$action} pada endpoint {$routeName} ({$statusCode})",
        };
    }

    private function buildLivewireDescription(
        string $action,
        ?string $componentName,
        string $calledMethod,
        string $targetHint,
        int $statusCode
    ): string {
        $resourceLabel = $this->resolveResourceLabel($componentName);

        $verb = match ($action) {
            'create' => 'Membuat',
            'update' => 'Memperbarui',
            'delete' => 'Menghapus',
            'login' => 'Login',
            'logout' => 'Logout',
            default => 'Menjalankan aksi',
        };

        $target = $targetHint !== '' ? " (ID: {$targetHint})" : '';

        if (in_array($action, ['login', 'logout'], true)) {
            return "{$verb} melalui komponen {$resourceLabel} ({$statusCode})";
        }

        return "{$verb} {$resourceLabel}{$target} ({$statusCode})";
    }

    private function resolveResourceLabel(?string $componentName): string
    {
        $component = strtolower((string) $componentName);

        return match (true) {
            str_contains($component, 'management.users') => 'data user',
            str_contains($component, 'monitor.mission-issues') => 'data masalah misi',
            str_contains($component, 'monitor.logs') => 'data log',
            str_contains($component, 'settings') => 'pengaturan akun',
            $component !== '' => "komponen {$component}",
            default => 'data sistem',
        };
    }
}
