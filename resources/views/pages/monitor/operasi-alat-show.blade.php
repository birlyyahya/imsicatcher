<?php

use App\Models\OperasiAlat;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Detail Log Operasi Alat')] class extends Component
{
    public OperasiAlat $operasiAlat;

    public function mount(OperasiAlat $operasiAlat): void
    {
        abort_unless($operasiAlat->isManageableBy(auth()->user()), 403);

        $this->operasiAlat = $operasiAlat->load(['operator', 'satker', 'missionIssue']);
    }

    public function render(): View
    {
        return view('pages.monitor.operasi-alat-show');
    }
};

?>
@assets
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@endassets
<div class="space-y-6">
    <header class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <flux:heading size="xl">Detail Log Operasi Alat #{{ $operasiAlat->id }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Informasi lengkap log operasi alat.</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:button :href="route('operasi-alat.pdf', $operasiAlat)" icon="arrow-down-tray" variant="filled">Cetak PDF</flux:button>
                <flux:button :href="route('operasi-alat.edit', $operasiAlat)" wire:navigate variant="primary">Edit</flux:button>
                <flux:button :href="route('operasi-alat')" wire:navigate variant="ghost">Kembali</flux:button>
            </div>
        </div>
    </header>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs uppercase text-zinc-500">Jenis Alat</p>
                <p class="mt-1 font-medium">{{ $operasiAlat->jenisAlatLabel() }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Hasil</p>
                <div class="mt-1">
                    @if ($operasiAlat->hasil === 'berhasil')
                        <flux:badge color="green" size="sm">Berhasil</flux:badge>
                    @elseif ($operasiAlat->hasil === 'gagal')
                        <flux:badge color="red" size="sm">Gagal</flux:badge>
                    @elseif ($operasiAlat->hasil === 'sebagian')
                        <flux:badge color="yellow" size="sm">Sebagian</flux:badge>
                    @else
                        <span class="text-sm text-zinc-500">Belum ditentukan</span>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Waktu Mulai</p>
                <p class="mt-1 font-medium">{{ $operasiAlat->waktu_mulai?->format('d-m-Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Waktu Selesai</p>
                <p class="mt-1 font-medium">{{ $operasiAlat->waktu_selesai?->format('d-m-Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Operator</p>
                <p class="mt-1 font-medium">{{ $operasiAlat->operator?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Satker</p>
                <p class="mt-1 font-medium">{{ $operasiAlat->satker?->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Keterangan Lokasi</p>
                <p class="mt-1 font-medium">{{ $operasiAlat->lokasi_keterangan ?: ($operasiAlat->lokasi ?: '-') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Masalah Misi Terkait</p>
                <p class="mt-1 font-medium">
                    @if ($operasiAlat->missionIssue)
                        <a href="{{ route('mission-issues.show', $operasiAlat->missionIssue) }}" wire:navigate class="text-blue-600 hover:underline">
                            #{{ $operasiAlat->missionIssue->id }} — {{ $operasiAlat->missionIssue->jenis }}
                        </a>
                    @else
                        -
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Lokasi Operasi</flux:heading>

        @if (filled($operasiAlat->latitude) && filled($operasiAlat->longitude))
            <div class="mt-3">
                @include('partials.operasi-alat-map', [
                    'interactive' => false,
                    'latitude' => $operasiAlat->latitude,
                    'longitude' => $operasiAlat->longitude,
                ])
            </div>
            <p class="mt-2 text-xs text-zinc-500">Koordinat: {{ $operasiAlat->latitude }}, {{ $operasiAlat->longitude }}</p>
        @else
            {{-- Fallback untuk data lama sebelum fitur peta: tampilkan kolom lokasi lama. --}}
            <p class="mt-2 text-sm text-zinc-500">{{ $operasiAlat->lokasi ?: 'Belum ada data lokasi pada log ini.' }}</p>
        @endif
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Tujuan Operasi</flux:heading>
        <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $operasiAlat->tujuan_operasi }}</p>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Catatan</flux:heading>
        <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $operasiAlat->catatan ?: '-' }}</p>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Foto Bukti</flux:heading>

        @if ($operasiAlat->foto_bukti)
            <div class="mt-3">
                @if (\Illuminate\Support\Str::startsWith($operasiAlat->foto_bukti, ['http://', 'https://']))
                    <a href="{{ $operasiAlat->foto_bukti }}" target="_blank" class="text-blue-600 hover:underline">Buka Foto Bukti</a>
                @else
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($operasiAlat->foto_bukti) }}" alt="Foto bukti operasi {{ $operasiAlat->id }}" class="max-h-[400px] rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                @endif
            </div>
        @else
            <p class="mt-2 text-sm text-zinc-500">Belum ada foto bukti.</p>
        @endif
    </section>
</div>
