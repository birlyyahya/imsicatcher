<?php

use App\Models\OperasiAlat;
use App\Models\Satker;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

new #[Layout('layouts.app'), Title('Log Operasi Alat')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterJenisAlat = '';
    public string $filterSatker = '';
    public string $filterHasil = '';
    public string $filterTanggalDari = '';
    public string $filterTanggalSampai = '';
    public int $perPage = 10;

    public function mount(): void
    {
        // Otorisasi modul dijaga lewat scopeVisibleTo; semua role boleh melihat
        // datanya masing-masing sesuai cakupan satker/kepemilikan.
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterJenisAlat(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSatker(): void
    {
        $this->resetPage();
    }

    public function updatedFilterHasil(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTanggalDari(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTanggalSampai(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function stats(): array
    {
        $user = auth()->user();

        return [
            'total' => OperasiAlat::query()->visibleTo($user)->count(),
            'berhasil' => OperasiAlat::query()->visibleTo($user)->where('hasil', 'berhasil')->count(),
            'gagal' => OperasiAlat::query()->visibleTo($user)->where('hasil', 'gagal')->count(),
            'sebagian' => OperasiAlat::query()->visibleTo($user)->where('hasil', 'sebagian')->count(),
        ];
    }

    #[Computed]
    public function jenisAlatOptions(): array
    {
        return OperasiAlat::JENIS_ALAT;
    }

    /**
     * Opsi satker untuk filter: superadmin melihat semua, admin hanya satkernya.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function satkerOptions(): array
    {
        $user = auth()->user();

        return Satker::query()
            ->when($user->isAdmin(), fn (Builder $query) => $query->whereKey($user->satker_id))
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    #[Computed]
    public function logs()
    {
        return OperasiAlat::query()
            ->visibleTo(auth()->user())
            ->with(['operator', 'satker', 'missionIssue'])
            ->when($this->search !== '', function (Builder $query) {
                $search = $this->search;
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('lokasi', 'like', "%{$search}%")
                        ->orWhere('lokasi_keterangan', 'like', "%{$search}%")
                        ->orWhere('tujuan_operasi', 'like', "%{$search}%")
                        ->orWhere('catatan', 'like', "%{$search}%");
                });
            })
            ->when($this->filterJenisAlat !== '', fn (Builder $query) => $query->where('jenis_alat', $this->filterJenisAlat))
            ->when($this->filterSatker !== '', fn (Builder $query) => $query->where('satker_id', $this->filterSatker))
            ->when($this->filterHasil !== '', fn (Builder $query) => $query->where('hasil', $this->filterHasil))
            ->when($this->filterTanggalDari !== '', fn (Builder $query) => $query->whereDate('waktu_mulai', '>=', $this->filterTanggalDari))
            ->when($this->filterTanggalSampai !== '', fn (Builder $query) => $query->whereDate('waktu_mulai', '<=', $this->filterTanggalSampai))
            ->latest('waktu_mulai')
            ->paginate($this->perPage);
    }

    /**
     * Hapus log operasi alat. Hanya boleh oleh pembuat (operator), admin satker,
     * atau superadmin — sama dengan aturan isManageableBy.
     */
    public function delete(OperasiAlat $log): void
    {
        abort_unless($log->isManageableBy(auth()->user()), 403);

        if ($log->foto_bukti && Storage::disk('public')->exists($log->foto_bukti)) {
            Storage::disk('public')->delete($log->foto_bukti);
        }

        $log->delete();

        Toaster::success('Log operasi alat berhasil dihapus.');
    }

    public function render(): View
    {
        return view('pages.monitor.operasi-alat');
    }
};

?>
<div>
    <div class="space-y-6">
        <header class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="xl">Log Operasi Alat</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Catat dan pantau setiap penggunaan alat operasi beserta hasilnya.
            </flux:text>
        </header>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Total Log</div>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->stats['total']) }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Berhasil</div>
                <div class="mt-2 text-2xl font-bold text-green-500">{{ number_format($this->stats['berhasil']) }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Gagal</div>
                <div class="mt-2 text-2xl font-bold text-red-500">{{ number_format($this->stats['gagal']) }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase text-zinc-500">Sebagian</div>
                <div class="mt-2 text-2xl font-bold text-yellow-500">{{ number_format($this->stats['sebagian']) }}</div>
            </div>
        </div>

        <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <form class="flex flex-wrap items-end gap-2">
                <flux:input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari lokasi / tujuan ..." class="w-full md:w-auto" />
                <flux:select wire:model.live="filterJenisAlat" class="w-full md:w-auto">
                    <flux:select.option value="">Semua Jenis Alat</flux:select.option>
                    @foreach ($this->jenisAlatOptions as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                @unless (auth()->user()->isOperator())
                    <flux:select wire:model.live="filterSatker" class="w-full md:w-auto">
                        <flux:select.option value="">Semua Satker</flux:select.option>
                        @foreach ($this->satkerOptions as $id => $nama)
                            <flux:select.option :value="$id">{{ $nama }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endunless
                <flux:select wire:model.live="filterHasil" class="w-full md:w-auto">
                    <flux:select.option value="">Semua Hasil</flux:select.option>
                    <flux:select.option value="berhasil">Berhasil</flux:select.option>
                    <flux:select.option value="gagal">Gagal</flux:select.option>
                    <flux:select.option value="sebagian">Sebagian</flux:select.option>
                </flux:select>
                <flux:input wire:model.live="filterTanggalDari" type="date" label="Dari" class="w-full md:w-auto" />
                <flux:input wire:model.live="filterTanggalSampai" type="date" label="Sampai" class="w-full md:w-auto" />
                <flux:button type="button" variant="ghost" wire:click="$set('search', ''); $set('filterJenisAlat', ''); $set('filterSatker', ''); $set('filterHasil', ''); $set('filterTanggalDari', ''); $set('filterTanggalSampai', '')">
                    Reset
                </flux:button>
            </form>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="lg">Daftar Log Operasi Alat</flux:heading>
                <flux:button :href="route('operasi-alat.create')" wire:navigate variant="primary">Tambah Log</flux:button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-100 text-left text-xs uppercase text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Waktu Mulai</th>
                            <th class="px-3 py-3 font-semibold">Waktu Selesai</th>
                            <th class="px-3 py-3 font-semibold">Jenis Alat</th>
                            <th class="px-3 py-3 font-semibold">Operator</th>
                            <th class="px-3 py-3 font-semibold">Satker</th>
                            <th class="px-3 py-3 font-semibold">Lokasi</th>
                            <th class="px-3 py-3 font-semibold">Tujuan</th>
                            <th class="px-3 py-3 font-semibold">Hasil</th>
                            <th class="px-3 py-3 font-semibold">Masalah Misi</th>
                            <th class="px-3 py-3 font-semibold">Foto Bukti</th>
                            <th class="px-3 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($this->logs as $log)
                            <tr class="align-top hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                                <td class="px-3 py-3 whitespace-nowrap">{{ $log->waktu_mulai?->format('d-m-Y H:i') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">{{ $log->waktu_selesai?->format('d-m-Y H:i') ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $log->jenisAlatLabel() }}</td>
                                <td class="px-3 py-3">{{ $log->operator?->name ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $log->satker?->nama ?? '-' }}</td>
                                <td class="px-3 py-3">
                                    <div>{{ $log->lokasi_keterangan ?: ($log->lokasi ?: '-') }}</div>
                                    @if (filled($log->latitude) && filled($log->longitude))
                                        <a href="{{ route('operasi-alat.show', $log) }}" wire:navigate class="mt-0.5 inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                            <flux:icon name="map-pin" class="size-3.5" /> Lihat Lokasi
                                        </a>
                                    @endif
                                </td>
                                <td class="px-3 py-3 max-w-xs">{{ \Illuminate\Support\Str::limit($log->tujuan_operasi, 70) }}</td>
                                <td class="px-3 py-3">
                                    @if ($log->hasil === 'berhasil')
                                        <flux:badge color="green" size="sm">Berhasil</flux:badge>
                                    @elseif ($log->hasil === 'gagal')
                                        <flux:badge color="red" size="sm">Gagal</flux:badge>
                                    @elseif ($log->hasil === 'sebagian')
                                        <flux:badge color="yellow" size="sm">Sebagian</flux:badge>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if ($log->mission_issue_id)
                                        <a href="{{ route('mission-issues.show', $log->mission_issue_id) }}" wire:navigate class="text-blue-600 hover:underline">#{{ $log->mission_issue_id }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($log->foto_bukti)
                                        <a href="{{ Storage::url($log->foto_bukti) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-3 space-x-2 whitespace-nowrap">
                                    <flux:button href="{{ route('operasi-alat.edit', $log) }}" variant="outline" size="sm" wire:navigate>Edit</flux:button>
                                    <flux:button href="{{ route('operasi-alat.show', $log) }}" variant="primary" size="sm" wire:navigate>Lihat</flux:button>
                                    <flux:button variant="danger" size="sm" wire:click="delete({{ $log->id }})" wire:confirm="Yakin hapus log operasi alat ini? Tindakan ini tidak dapat dibatalkan.">Hapus</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-3 py-8 text-center text-zinc-500">Belum ada data log operasi alat.</td>
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
</div>
