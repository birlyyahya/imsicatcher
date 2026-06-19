<?php

use App\Models\MissionIssue;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app'), Title('Masalah Misi')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterJenis = '';
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterJenis(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function stats(): array
    {
        $user = auth()->user();

        return [
            'total' => MissionIssue::query()->visibleTo($user)->count(),
            'baru' => MissionIssue::query()->visibleTo($user)->where('status', 'baru')->count(),
            'proses' => MissionIssue::query()->visibleTo($user)->where('status', 'proses')->count(),
            'selesai' => MissionIssue::query()->visibleTo($user)->where('status', 'selesai')->count(),
        ];
    }

    #[Computed]
    public function jenisOptions(): array
    {
        return MissionIssue::query()
            ->visibleTo(auth()->user())
            ->select('jenis')
            ->whereNotNull('jenis')
            ->distinct()
            ->orderBy('jenis')
            ->pluck('jenis')
            ->values()
            ->all();
    }

    #[Computed]
    public function issues()
    {
        return MissionIssue::query()
            ->visibleTo(auth()->user())
            ->with('creator')
            ->when($this->search !== '', function (Builder $query) {
                $search = $this->search;
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('deskripsi', 'like', "%{$search}%")
                        ->orWhere('lokasi', 'like', "%{$search}%")
                        ->orWhere('pelapor', 'like', "%{$search}%")
                        ->orWhere('satker', 'like', "%{$search}%");
                });
            })
            ->when($this->filterStatus !== '', fn (Builder $query) => $query->where('status', $this->filterStatus))
            ->when($this->filterJenis !== '', fn (Builder $query) => $query->where('jenis', $this->filterJenis))
            ->latest('tanggal')
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('pages.monitor.mission-issues');
    }
};

?>
<div>
    <div class="space-y-6">
        <header class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="xl">Masalah Misi</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Pantau, tindak lanjuti, dan dokumentasikan setiap kejadian yang mempengaruhi misi.
            </flux:text>
        </header>

        @if (session('success'))
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 3000)"
                x-show="show"
                x-transition.opacity.duration.300ms
                class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300"
            >
                <span>{{ session('success') }}</span>
                <button type="button" class="text-emerald-700/80 hover:text-emerald-900 dark:text-emerald-300/80 dark:hover:text-emerald-100" @click="show = false">
                    <flux:icon name="x-mark" size="sm"></flux:icon>
                </button>
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Total Laporan</div>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->stats['total']) }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Baru</div>
                <div class="mt-2 text-2xl font-bold text-yellow-500">{{ number_format($this->stats['baru']) }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Dalam Proses</div>
                <div class="mt-2 text-2xl font-bold text-blue-500">{{ number_format($this->stats['proses']) }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Selesai</div>
                <div class="mt-2 text-2xl font-bold text-green-500">{{ number_format($this->stats['selesai']) }}</div>
            </div>
        </div>

        <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <form class="flex flex-wrap items-center gap-2">
                <flux:input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari masalah misi ..." class="w-full md:w-auto" />
                <flux:select wire:model.live="filterStatus" class="w-full md:w-auto">
                    <flux:select.option value="">Semua Status</flux:select.option>
                    <flux:select.option value="baru">Baru</flux:select.option>
                    <flux:select.option value="proses">Dalam Proses</flux:select.option>
                    <flux:select.option value="selesai">Selesai</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="filterJenis" class="w-full md:w-auto">
                    <flux:select.option value="">Semua Jenis</flux:select.option>
                    @foreach ($this->jenisOptions as $jenisOption)
                        <flux:select.option :value="$jenisOption">{{ $jenisOption }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button type="button" variant="ghost" wire:click="$set('search', ''); $set('filterStatus', ''); $set('filterJenis', '')">
                    Reset
                </flux:button>
            </form>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="lg">Daftar Masalah Misi</flux:heading>
                <flux:button :href="route('mission-issues.create')" wire:navigate variant="primary">Tambah Misi</flux:button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-100 text-left text-xs uppercase text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Tanggal</th>
                            <th class="px-3 py-3 font-semibold">Lokasi</th>
                            <th class="px-3 py-3 font-semibold">Jenis</th>
                            <th class="px-3 py-3 font-semibold">Deskripsi</th>
                            <th class="px-3 py-3 font-semibold">Pelapor</th>
                            <th class="px-3 py-3 font-semibold">Pihak Terlibat</th>
                            <th class="px-3 py-3 font-semibold">Satker</th>
                            <th class="px-3 py-3 font-semibold">Tindakan</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-3 py-3 font-semibold">Foto Bukti</th>
                            <th class="px-3 py-3 font-semibold">Dibuat Oleh</th>
                            <th class="px-3 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($this->issues as $issue)
                            <tr class="align-top hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                                <td class="px-3 py-3 whitespace-nowrap">{{ $issue->tanggal?->format('d-m-Y H:i') }}</td>
                                <td class="px-3 py-3">{{ $issue->lokasi }}</td>
                                <td class="px-3 py-3">{{ $issue->jenis }}</td>
                                <td class="px-3 py-3 max-w-xs">{{ \Illuminate\Support\Str::limit($issue->deskripsi, 90) }}</td>
                                <td class="px-3 py-3">{{ $issue->pelapor }}</td>
                                <td class="px-3 py-3">{{ $issue->pihak_terlibat ?: '-' }}</td>
                                <td class="px-3 py-3">{{ $issue->satker }}</td>
                                <td class="px-3 py-3 max-w-xs">{{ $issue->tindakan ? \Illuminate\Support\Str::limit($issue->tindakan, 70) : '-' }}</td>
                                <td class="px-3 py-3">
                                    @if ($issue->status === 'selesai')
                                        <flux:badge color="green" size="sm">Selesai</flux:badge>
                                    @elseif ($issue->status === 'proses')
                                        <flux:badge color="blue" size="sm">Dalam Proses</flux:badge>
                                    @else
                                        <flux:badge color="yellow" size="sm">Baru</flux:badge>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($issue->foto_bukti)
                                        <a href="{{ Storage::url($issue->foto_bukti) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-3">{{ $issue->creator?->name ?? '-' }}</td>
                                    <td class="px-3 py-3 space-x-2 whitespace-nowrap">
                                        <flux:button href="{{ route('mission-issues.edit', $issue) }}" variant="outline" size="sm" wire:navigate>Edit</flux:button>
                                    <flux:button href="{{ route('mission-issues.show', $issue) }}" variant="primary" size="sm" wire:navigate>Lihat</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-3 py-8 text-center text-zinc-500">Belum ada data masalah misi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $this->issues->links() }}
            </div>
        </section>
    </div>
</div>
