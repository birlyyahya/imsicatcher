@php
    use App\Models\ActivityLog;
    use App\Models\MissionIssue;
    use App\Models\User;

    $authUser = auth()->user();

    // Scope user sesuai role: superadmin semua, admin hanya satkernya.
    $userQuery = User::query()
        ->when($authUser->isAdmin(), fn ($q) => $q->where('satker', $authUser->satker));

    $totalUsers = (clone $userQuery)->count();
    $operatorCount = (clone $userQuery)->where('role', 'operator')->count();
    $adminCount = (clone $userQuery)->where('role', 'admin')->count();
    $superadminCount = (clone $userQuery)->where('role', 'superadmin')->count();

    $totalIssues = MissionIssue::query()->visibleTo($authUser)->count();
    $issuesBaru = MissionIssue::query()->visibleTo($authUser)->where('status', 'baru')->count();
    $issuesProses = MissionIssue::query()->visibleTo($authUser)->where('status', 'proses')->count();
    $issuesSelesai = MissionIssue::query()->visibleTo($authUser)->where('status', 'selesai')->count();

    $totalLogs = ActivityLog::query()->visibleTo($authUser)->count();
    $logsToday = ActivityLog::query()->visibleTo($authUser)->whereDate('logged_at', now()->toDateString())->count();
    $logsDelete = ActivityLog::query()->visibleTo($authUser)->where('action', 'delete')->count();

    $recentIssues = MissionIssue::query()->visibleTo($authUser)->latest('tanggal')->limit(5)->get();
    $recentLogs = ActivityLog::query()->visibleTo($authUser)->latest('logged_at')->limit(8)->get();
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div class="space-y-6">
        <header class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="xl">Dashboard Monitoring IMSI Catcher</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Ringkasan cepat operasional: masalah misi, aktivitas pengguna, dan kesehatan alur monitoring.
            </flux:text>
        </header>

        <div class="grid gap-4 md:grid-cols-4">
            @unless ($authUser->isOperator())
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Total User{{ $authUser->isAdmin() ? ' (Satker Anda)' : '' }}</div>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($totalUsers) }}</div>
                <div class="mt-1 text-xs text-zinc-500">
                    @if ($authUser->isSuperadmin())
                        Operator {{ $operatorCount }} | Admin {{ $adminCount }} | Superadmin {{ $superadminCount }}
                    @else
                        Operator {{ $operatorCount }} | Admin {{ $adminCount }}
                    @endif
                </div>
            </div>
            @endunless
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Masalah Misi</div>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($totalIssues) }}</div>
                <div class="mt-1 text-xs text-zinc-500">Baru {{ $issuesBaru }} | Proses {{ $issuesProses }} | Selesai {{ $issuesSelesai }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Total Aktivitas</div>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($totalLogs) }}</div>
                <div class="mt-1 text-xs text-zinc-500">Log hari ini: {{ number_format($logsToday) }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Aksi Penghapusan</div>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($logsDelete) }}</div>
                <div class="mt-1 text-xs text-zinc-500">Total aksi delete terekam</div>
            </div>
        </div>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-3">
                <flux:heading size="lg">Aksi Cepat</flux:heading>
            </div>
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
                <flux:button :href="route('mission-issues.create')" wire:navigate variant="primary">Tambah Masalah Misi</flux:button>
                <flux:button :href="route('mission-issues')" wire:navigate variant="ghost">Lihat Daftar Misi</flux:button>
                <flux:button :href="route('logs')" wire:navigate variant="ghost">Monitor Log</flux:button>
                @unless ($authUser->isOperator())
                    <flux:button :href="route('users')" wire:navigate variant="ghost">Kelola User</flux:button>
                @endunless
                <flux:button :href="route('network-traffic')" wire:navigate variant="ghost">Network Traffic</flux:button>
            </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="lg">Masalah Misi Terbaru</flux:heading>
                    <a href="{{ route('mission-issues') }}" wire:navigate class="text-sm text-blue-600 hover:underline">Lihat semua</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-100 text-left text-xs uppercase text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            <tr>
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Lokasi</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($recentIssues as $issue)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $issue->tanggal?->format('d-m-Y H:i') }}</td>
                                    <td class="px-3 py-2">{{ $issue->lokasi }}</td>
                                    <td class="px-3 py-2">
                                        @if ($issue->status === 'selesai')
                                            <flux:badge color="green" size="sm">Selesai</flux:badge>
                                        @elseif ($issue->status === 'proses')
                                            <flux:badge color="blue" size="sm">Dalam Proses</flux:badge>
                                        @else
                                            <flux:badge color="yellow" size="sm">Baru</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('mission-issues.show', $issue) }}" wire:navigate class="text-blue-600 hover:underline">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-5 text-center text-zinc-500">Belum ada masalah misi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="lg">Aktivitas Terbaru</flux:heading>
                    <a href="{{ route('logs') }}" wire:navigate class="text-sm text-blue-600 hover:underline">Buka log</a>
                </div>

                <div class="space-y-3">
                    @forelse ($recentLogs as $log)
                        <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-sm font-medium">{{ $log->user_name ?: 'Guest' }} - {{ $log->description }}</div>
                                    <div class="text-xs text-zinc-500">{{ $log->agent ?: '-' }} | {{ $log->ip_address ?: '-' }}</div>
                                </div>
                                <div class="text-xs whitespace-nowrap text-zinc-500">{{ $log->logged_at?->format('d-m H:i:s') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-zinc-200 px-3 py-4 text-center text-sm text-zinc-500 dark:border-zinc-700">
                            Belum ada aktivitas yang tercatat.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
