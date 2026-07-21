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
        {{-- Hero + statistik menyatu sebagai tile glass --}}
        <header class="page-hero">
            <flux:heading size="xl">Dashboard Monitoring IMSI Catcher</flux:heading>
            <flux:text class="mt-1 text-sm">
                Ringkasan cepat operasional: incident, aktivitas pengguna, dan kesehatan alur monitoring.
            </flux:text>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @unless ($authUser->isOperator())
                <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                    <div class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-indigo-200/70">Total User{{ $authUser->isAdmin() ? ' (Satker Anda)' : '' }}</div>
                    <div class="mt-1 font-display text-3xl font-bold tracking-tight text-white">{{ number_format($totalUsers) }}</div>
                    <div class="mt-1 text-xs text-indigo-200/60">
                        @if ($authUser->isSuperadmin())
                            Operator {{ $operatorCount }} | Admin {{ $adminCount }} | Superadmin {{ $superadminCount }}
                        @else
                            Operator {{ $operatorCount }} | Admin {{ $adminCount }}
                        @endif
                    </div>
                </div>
                @endunless
                <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                    <div class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-indigo-200/70">Incidents</div>
                    <div class="mt-1 font-display text-3xl font-bold tracking-tight text-white">{{ number_format($totalIssues) }}</div>
                    <div class="mt-1 text-xs text-indigo-200/60">Baru {{ $issuesBaru }} | Proses {{ $issuesProses }} | Selesai {{ $issuesSelesai }}</div>
                </div>
                <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                    <div class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-indigo-200/70">Total Aktivitas</div>
                    <div class="mt-1 font-display text-3xl font-bold tracking-tight text-white">{{ number_format($totalLogs) }}</div>
                    <div class="mt-1 text-xs text-indigo-200/60">Log hari ini: {{ number_format($logsToday) }}</div>
                </div>
                <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                    <div class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-indigo-200/70">Aksi Penghapusan</div>
                    <div class="mt-1 font-display text-3xl font-bold tracking-tight text-white">{{ number_format($logsDelete) }}</div>
                    <div class="mt-1 text-xs text-indigo-200/60">Total aksi delete terekam</div>
                </div>
            </div>
        </header>

        {{-- Grid asimetris: konten utama kiri, panel aksi & aktivitas kanan --}}
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="lg">Incident Terbaru</flux:heading>
                    <a href="{{ route('mission-issues') }}" wire:navigate class="text-sm text-indigo-600 hover:underline">Lihat semua</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-indigo-50/80 text-left text-xs uppercase tracking-wide text-indigo-950/80 dark:bg-zinc-800 dark:text-zinc-300">
                            <tr>
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Lokasi</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($recentIssues as $issue)
                                <tr class="transition-colors hover:bg-indigo-50/40 dark:hover:bg-zinc-800/70">
                                    <td class="px-3 py-2 font-mono text-xs whitespace-nowrap">{{ $issue->tanggal?->format('d-m-Y H:i') }}</td>
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
                                        <a href="{{ route('mission-issues.show', $issue) }}" wire:navigate class="text-indigo-600 hover:underline">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-5 text-center text-zinc-500">Belum ada Incident.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="space-y-6">
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3">
                        <flux:heading size="lg">Aksi Cepat</flux:heading>
                    </div>
                    <div class="grid gap-2">
                        <flux:button :href="route('mission-issues.create')" wire:navigate variant="primary" icon="plus" class="justify-start!">Tambah Incidents</flux:button>
                        <flux:button :href="route('mission-issues')" wire:navigate variant="ghost" icon="folder" class="justify-start!">Lihat Daftar Misi</flux:button>
                        <flux:button :href="route('logs')" wire:navigate variant="ghost" icon="inbox-arrow-down" class="justify-start!">Monitor Log</flux:button>
                        @unless ($authUser->isOperator())
                            <flux:button :href="route('users')" wire:navigate variant="ghost" icon="users" class="justify-start!">Kelola User</flux:button>
                        @endunless
                        <flux:button :href="route('network-traffic')" wire:navigate variant="ghost" icon="signal" class="justify-start!">Network Traffic</flux:button>
                    </div>
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="lg">Aktivitas Terbaru</flux:heading>
                        <a href="{{ route('logs') }}" wire:navigate class="text-sm text-indigo-600 hover:underline">Buka log</a>
                    </div>

                    <ol class="relative space-y-4 border-s border-zinc-200 ps-4 dark:border-zinc-700">
                        @forelse ($recentLogs as $log)
                            <li class="relative">
                                <span class="absolute -left-[21.5px] top-1.5 size-2.5 rounded-full bg-indigo-500 ring-4 ring-indigo-100 dark:ring-zinc-800"></span>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium">{{ $log->user_name ?: 'Guest' }} - {{ $log->description }}</div>
                                        <div class="truncate text-xs text-zinc-500">{{ $log->agent ?: '-' }} | {{ $log->ip_address ?: '-' }}</div>
                                    </div>
                                    <div class="font-mono text-xs whitespace-nowrap text-zinc-500">{{ $log->logged_at?->format('d-m H:i:s') }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="relative text-sm text-zinc-500">
                                Belum ada aktivitas yang tercatat.
                            </li>
                        @endforelse
                    </ol>
                </section>
            </div>
        </div>
    </div>
</x-layouts::app>
