<?php

use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app'), Title('Log Aktivitas')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $actionFilter = '';
    public string $agentFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 15;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfDay()->format('Y-m-d\TH:i');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAgentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function stats(): array
    {
        $user = auth()->user();

        return [
            'total' => ActivityLog::query()->visibleTo($user)->count(),
            'today' => ActivityLog::query()->visibleTo($user)->whereDate('logged_at', today())->count(),
            'users' => ActivityLog::query()->visibleTo($user)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'livewire' => ActivityLog::query()->visibleTo($user)->where('action', 'like', 'livewire:%')->count(),
        ];
    }

    #[Computed]
    public function actionOptions(): array
    {
        return ActivityLog::query()
            ->visibleTo(auth()->user())
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values()
            ->all();
    }

    #[Computed]
    public function agentOptions(): array
    {
        return ActivityLog::query()
            ->visibleTo(auth()->user())
            ->select('agent')
            ->whereNotNull('agent')
            ->distinct()
            ->orderBy('agent')
            ->pluck('agent')
            ->values()
            ->all();
    }

    #[Computed]
    public function logs()
    {
        return ActivityLog::query()
            ->visibleTo(auth()->user())
            ->with('user')
            ->when($this->search !== '', function (Builder $query) {
                $search = $this->search;
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('agent', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->when($this->actionFilter !== '', fn (Builder $query) => $query->where('action', $this->actionFilter))
            ->when($this->agentFilter !== '', fn (Builder $query) => $query->where('agent', $this->agentFilter))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->where('logged_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->where('logged_at', '<=', $this->dateTo))
            ->latest('logged_at')
            ->paginate($this->perPage);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->actionFilter = '';
        $this->agentFilter = '';
        $this->dateFrom = now()->startOfDay()->format('Y-m-d\TH:i');
        $this->dateTo = '';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('pages.monitor.logs');
    }
};

?>
<div class="space-y-6">
    <header class="page-hero">
        <flux:heading size="xl">Log Aktivitas Sistem</flux:heading>
        <flux:text class="mt-1 text-sm">
            Semua aktivitas website tercatat otomatis, termasuk navigasi halaman dan aksi pada komponen Livewire.
        </flux:text>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                <div class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-indigo-200/70">Total Log</div>
                <div class="mt-1 font-display text-3xl font-bold tracking-tight text-white">{{ number_format($this->stats['total']) }}</div>
            </div>
            <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                <div class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-indigo-200/70">Hari Ini</div>
                <div class="mt-1 font-display text-3xl font-bold tracking-tight text-sky-300">{{ number_format($this->stats['today']) }}</div>
            </div>
            <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                <div class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-indigo-200/70">User Aktif Tercatat</div>
                <div class="mt-1 font-display text-3xl font-bold tracking-tight text-emerald-300">{{ number_format($this->stats['users']) }}</div>
            </div>
            <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                <div class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-indigo-200/70">Aksi Livewire</div>
                <div class="mt-1 font-display text-3xl font-bold tracking-tight text-violet-300">{{ number_format($this->stats['livewire']) }}</div>
            </div>
        </div>
    </header>

    <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="lg">Daftar Aktivitas</flux:heading>
            <flux:text class="text-xs text-zinc-500">Menampilkan data terbaru terlebih dahulu.</flux:text>
        </div>

        <div class="mb-4 rounded-xl bg-zinc-50 p-3 ring-1 ring-zinc-200/70 dark:bg-zinc-800/50 dark:ring-zinc-700">
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-6">
            <flux:input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari deskripsi/user/ip..." class="lg:col-span-2" />

            <flux:select wire:model.live="actionFilter">
                <flux:select.option value="">Semua Aksi</flux:select.option>
                @foreach ($this->actionOptions as $actionOption)
                    <flux:select.option :value="$actionOption">{{ $actionOption }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="agentFilter">
                <flux:select.option value="">Semua Agent</flux:select.option>
                @foreach ($this->agentOptions as $agentOption)
                    <flux:select.option :value="$agentOption">{{ $agentOption }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="dateFrom" type="datetime-local" />
            <flux:input wire:model.live="dateTo" type="datetime-local" />
        </div>

        <div class="mt-3">
            <flux:button wire:click="resetFilters" type="button" variant="ghost">Reset Filter</flux:button>
        </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-indigo-50/80 text-left text-xs uppercase tracking-wide text-indigo-950/80 dark:bg-zinc-800 dark:text-zinc-300">
                    <tr>
                        <th class="px-3 py-3 font-semibold">Waktu</th>
                        <th class="px-3 py-3 font-semibold">User</th>
                        <th class="px-3 py-3 font-semibold">Aksi</th>
                        <th class="px-3 py-3 font-semibold">Agent</th>
                        <th class="px-3 py-3 font-semibold">IP</th>
                        <th class="px-3 py-3 font-semibold">Deskripsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->logs as $log)
                        <tr class="align-top transition-colors hover:bg-indigo-50/40 dark:hover:bg-zinc-800/70">
                            <td class="px-3 py-3 font-mono text-xs whitespace-nowrap">{{ $log->logged_at?->format('d-m-Y H:i:s') }}</td>
                            <td class="px-3 py-3">{{ $log->user_name ?: 'Guest' }}</td>
                            <td class="px-3 py-3">
                                @if (str_starts_with($log->action, 'livewire:'))
                                    <flux:badge color="violet" size="sm">{{ $log->action }}</flux:badge>
                                @elseif ($log->action === 'view')
                                    <flux:badge color="blue" size="sm">{{ $log->action }}</flux:badge>
                                @elseif ($log->action === 'delete')
                                    <flux:badge color="red" size="sm">{{ $log->action }}</flux:badge>
                                @elseif ($log->action === 'update')
                                    <flux:badge color="yellow" size="sm">{{ $log->action }}</flux:badge>
                                @else
                                    <flux:badge color="green" size="sm">{{ $log->action }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-3 py-3">{{ $log->agent ?: '-' }}</td>
                            <td class="px-3 py-3 font-mono text-xs">{{ $log->ip_address ?: '-' }}</td>
                            <td class="px-3 py-3 max-w-xl">{{ $log->description ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-zinc-500">Belum ada aktivitas yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->logs->links() }}
        </div>
    </section>
</div>

