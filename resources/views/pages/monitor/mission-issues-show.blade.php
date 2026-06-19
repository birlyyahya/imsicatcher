<?php

use App\Models\MissionIssue;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Detail Masalah Misi')] class extends Component
{
    public MissionIssue $issue;

    public function mount(MissionIssue $issue): void
    {
        abort_unless($issue->isManageableBy(auth()->user()), 403);

        $this->issue = $issue->load('creator');
    }

    public function render(): View
    {
        return view('pages.monitor.mission-issues-show');
    }
};

?>
<div class="space-y-6">
    <header class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <flux:heading size="xl">Detail Masalah Misi #{{ $issue->id }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Informasi lengkap laporan masalah misi.</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:button :href="route('mission-issues.edit', $issue)" wire:navigate variant="primary">Edit</flux:button>
                <flux:button :href="route('mission-issues')" wire:navigate variant="ghost">Kembali</flux:button>
            </div>
        </div>
    </header>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs uppercase text-zinc-500">Tanggal</p>
                <p class="mt-1 font-medium">{{ $issue->tanggal?->format('d-m-Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Lokasi</p>
                <p class="mt-1 font-medium">{{ $issue->lokasi }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Jenis</p>
                <p class="mt-1 font-medium">{{ $issue->jenis }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Status</p>
                <div class="mt-1">
                    @if ($issue->status === 'selesai')
                        <flux:badge color="green" size="sm">Selesai</flux:badge>
                    @elseif ($issue->status === 'proses')
                        <flux:badge color="blue" size="sm">Dalam Proses</flux:badge>
                    @else
                        <flux:badge color="yellow" size="sm">Baru</flux:badge>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Pelapor</p>
                <p class="mt-1 font-medium">{{ $issue->pelapor }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Satker</p>
                <p class="mt-1 font-medium">{{ $issue->satker }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Pihak Terlibat</p>
                <p class="mt-1 font-medium">{{ $issue->pihak_terlibat ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">Dibuat Oleh</p>
                <p class="mt-1 font-medium">{{ $issue->creator?->name ?? '-' }}</p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Deskripsi</flux:heading>
        <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $issue->deskripsi }}</p>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Tindakan</flux:heading>
        <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $issue->tindakan ?: '-' }}</p>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Foto Bukti</flux:heading>

        @if ($issue->foto_bukti)
            <div class="mt-3">
                @if (\Illuminate\Support\Str::startsWith($issue->foto_bukti, ['http://', 'https://']))
                    <a href="{{ $issue->foto_bukti }}" target="_blank" class="text-blue-600 hover:underline">Buka Foto Bukti</a>
                @else
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($issue->foto_bukti) }}" alt="Foto bukti issue {{ $issue->id }}" class="max-h-[400px] rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                @endif
            </div>
        @else
            <p class="mt-2 text-sm text-zinc-500">Belum ada foto bukti.</p>
        @endif
    </section>
</div>
