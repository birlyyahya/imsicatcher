<?php

use App\Models\MissionIssue;
use App\Models\Satker;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app'), Title('Tambah Masalah Misi')] class extends Component
{
    use WithFileUploads;

    public string $tanggal = '';
    public string $lokasi = '';
    public string $jenis = '';
    public string $deskripsi = '';
    public string $pelapor = '';
    public string $pihakTerlibat = '';
    public string $satker = '';
    public string $tindakan = '';
    public string $status = 'baru';
    public mixed $foto = null;

    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d\TH:i');

        // Admin & operator terkunci pada satkernya sendiri; hanya superadmin bebas memilih.
        if (! auth()->user()->isSuperadmin()) {
            $this->satker = auth()->user()->satker ?? '';
        }
    }

    #[Computed]
    public function satkerOptions(): array
    {
        return Satker::options();
    }

    protected function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'lokasi' => 'required|string|max:255',
            'jenis' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'pelapor' => 'required|string|max:255',
            'pihakTerlibat' => 'nullable|string',
            'satker' => 'required|string|max:255',
            'tindakan' => 'nullable|string',
            'status' => 'required|in:baru,proses,selesai',
            'foto' => 'nullable|image|max:5120',
        ];
    }

    public function save(): void
    {
        // Kunci satker di sisi server agar tidak bisa diubah lewat request.
        if (! auth()->user()->isSuperadmin()) {
            $this->satker = auth()->user()->satker ?? '';
        }

        $validated = $this->validate();

        $payload = [
            'tanggal' => $validated['tanggal'],
            'lokasi' => $validated['lokasi'],
            'jenis' => $validated['jenis'],
            'deskripsi' => $validated['deskripsi'],
            'pelapor' => $validated['pelapor'],
            'pihak_terlibat' => $validated['pihakTerlibat'] ?: null,
            'satker' => $validated['satker'],
            'tindakan' => $validated['tindakan'] ?: null,
            'status' => $validated['status'],
            'dibuat_oleh' => auth()->id(),
        ];

        if ($this->foto) {
            $payload['foto_bukti'] = $this->foto->store('mission-issues', 'public');
        }

        MissionIssue::query()->create($payload);

        session()->flash('success', 'Data masalah misi berhasil ditambahkan.');
        $this->redirectRoute('mission-issues', navigate: true);
    }

    public function render(): View
    {
        return view('pages.monitor.mission-issues-create');
    }
};

?>
<div class="space-y-6">
    <header class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-2">
            <div>
                <flux:heading size="xl">Tambah Masalah Misi</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Isi data laporan masalah misi secara lengkap.</flux:text>
            </div>
            <flux:button :href="route('mission-issues')" wire:navigate variant="ghost">Kembali</flux:button>
        </div>
    </header>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <form wire:submit="save" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <flux:input wire:model="tanggal" type="datetime-local" label="Tanggal & Waktu" />
                    @error('tanggal') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input wire:model="lokasi" type="text" label="Lokasi" placeholder="Contoh: Posko Delta" />
                    @error('lokasi') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input wire:model="jenis" type="text" label="Jenis" placeholder="Contoh: Gangguan Jaringan" />
                    @error('jenis') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input wire:model="pelapor" type="text" label="Pelapor" placeholder="Nama pelapor" />
                    @error('pelapor') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input wire:model="pihakTerlibat" type="text" label="Pihak Terlibat" placeholder="Contoh: Tim Radio, Vendor Link" />
                    @error('pihakTerlibat') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    @if (auth()->user()->isSuperadmin())
                        <flux:select wire:model="satker" label="Satker">
                            <flux:select.option value="">Pilih Satker</flux:select.option>
                            @foreach ($this->satkerOptions as $satkerOption)
                                <flux:select.option :value="$satkerOption">{{ $satkerOption }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <flux:input wire:model="satker" type="text" label="Satker" readonly />
                    @endif
                    @unless (auth()->user()->isSuperadmin())
                        <p class="mt-1 text-xs text-zinc-500">Satker terkunci sesuai akun Anda.</p>
                    @endunless
                    @error('satker') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Deskripsi</label>
                <textarea wire:model="deskripsi" rows="3" placeholder="Jelaskan kronologi masalah yang terjadi..." class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                @error('deskripsi') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Tindakan</label>
                <textarea wire:model="tindakan" rows="3" placeholder="Tindakan penanganan yang sudah/akan dilakukan..." class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                @error('tindakan') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Status</label>
                    <flux:select wire:model="status">
                        <flux:select.option value="baru">Baru</flux:select.option>
                        <flux:select.option value="proses">Dalam Proses</flux:select.option>
                        <flux:select.option value="selesai">Selesai</flux:select.option>
                    </flux:select>
                    @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Foto Bukti</label>
                    <input wire:model="foto" type="file" accept="image/*" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" />
                    <p class="mt-1 text-xs text-zinc-500">Format: jpg, jpeg, png, webp. Maksimal 5MB.</p>
                    @error('foto') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            @if ($foto)
                <div>
                    <p class="mb-1 text-xs uppercase text-zinc-500">Preview Foto</p>
                    <img src="{{ $foto->temporaryUrl() }}" alt="Preview foto bukti" class="max-h-48 rounded-lg border border-zinc-200 dark:border-zinc-700">
                </div>
            @endif

            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary">Simpan Data</flux:button>
                <flux:button :href="route('mission-issues')" wire:navigate variant="ghost" type="button">Batal</flux:button>
            </div>
        </form>
    </section>
</div>
