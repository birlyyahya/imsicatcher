<?php

use App\Models\MissionIssue;
use App\Models\OperasiAlat;
use App\Models\Satker;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Masmerise\Toaster\Toaster;

new #[Layout('layouts.app'), Title('Tambah Log Operasi Alat')] class extends Component
{
    use WithFileUploads;

    public string $jenisAlat = '';
    public ?int $satkerId = null;
    public string $waktuMulai = '';
    public ?string $waktuSelesai = '';
    public ?string $lokasi = '';
    public string $tujuanOperasi = '';
    public ?string $hasil = '';
    public ?int $missionIssueId = null;
    public ?string $catatan = '';
    public mixed $foto = null;

    public function mount(): void
    {
        $this->waktuMulai = now()->format('Y-m-d\TH:i');

        // Admin & operator terkunci pada satkernya sendiri; hanya superadmin bebas memilih.
        if (! auth()->user()->isSuperadmin()) {
            $this->satkerId = auth()->user()->satker_id;
        }
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function satkerOptions(): array
    {
        return Satker::query()->orderBy('nama')->pluck('nama', 'id')->all();
    }

    #[Computed]
    public function jenisAlatOptions(): array
    {
        return OperasiAlat::JENIS_ALAT;
    }

    #[Computed]
    public function missionIssueOptions()
    {
        return MissionIssue::query()
            ->visibleTo(auth()->user())
            ->latest('tanggal')
            ->limit(100)
            ->get(['id', 'jenis', 'lokasi', 'tanggal']);
    }

    protected function rules(): array
    {
        return [
            'jenisAlat' => 'required|in:stingray_interceptor,hhdf,drone,laptop_operasi',
            'satkerId' => 'required|exists:satkers,id',
            'waktuMulai' => 'required|date',
            'waktuSelesai' => 'nullable|date|after_or_equal:waktuMulai',
            'lokasi' => 'nullable|string|max:255',
            'tujuanOperasi' => 'required|string',
            'hasil' => 'nullable|in:berhasil,gagal,sebagian',
            'missionIssueId' => 'nullable|integer|exists:mission_issues,id',
            'catatan' => 'nullable|string',
            'foto' => 'nullable|image|max:10240',
        ];
    }

    public function save(): void
    {
        // Kunci satker di sisi server agar tidak bisa diubah lewat request.
        if (! auth()->user()->isSuperadmin()) {
            $this->satkerId = auth()->user()->satker_id;
        }

        // Normalisasi string kosong dari <select>/<input> menjadi null sebelum validasi.
        foreach (['waktuSelesai', 'lokasi', 'hasil', 'catatan'] as $field) {
            if ($this->{$field} === '') {
                $this->{$field} = null;
            }
        }

        $validated = $this->validate();

        $payload = [
            'jenis_alat' => $validated['jenisAlat'],
            // Operator dikunci ke akun yang sedang login — tidak boleh memilih operator lain.
            'operator_id' => auth()->id(),
            'satker_id' => $validated['satkerId'],
            'waktu_mulai' => $validated['waktuMulai'],
            'waktu_selesai' => $validated['waktuSelesai'] ?: null,
            'lokasi' => $validated['lokasi'] ?: null,
            'tujuan_operasi' => $validated['tujuanOperasi'],
            'hasil' => $validated['hasil'] ?: null,
            'mission_issue_id' => $validated['missionIssueId'] ?: null,
            'catatan' => $validated['catatan'] ?: null,
        ];

        if ($this->foto) {
            $payload['foto_bukti'] = $this->foto->store('operasi-alat', 'public');
        }

        OperasiAlat::query()->create($payload);

        Toaster::success('Log operasi alat berhasil ditambahkan.');
        $this->redirectRoute('operasi-alat', navigate: true);
    }

    public function render(): View
    {
        return view('pages.monitor.operasi-alat-create');
    }
};

?>
<div class="space-y-6">
    <header class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-2">
            <div>
                <flux:heading size="xl">Tambah Log Operasi Alat</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Catat penggunaan alat operasi secara lengkap.</flux:text>
            </div>
            <flux:button :href="route('operasi-alat')" wire:navigate variant="ghost">Kembali</flux:button>
        </div>
    </header>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <form wire:submit="save" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Jenis Alat</label>
                    <flux:select wire:model="jenisAlat">
                        <flux:select.option value="">Pilih Jenis Alat</flux:select.option>
                        @foreach ($this->jenisAlatOptions as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('jenisAlat') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    @if (auth()->user()->isSuperadmin())
                        <label class="mb-1 block text-sm font-medium">Satker</label>
                        <flux:select wire:model="satkerId">
                            <flux:select.option value="">Pilih Satker</flux:select.option>
                            @foreach ($this->satkerOptions as $id => $nama)
                                <flux:select.option :value="$id">{{ $nama }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <flux:input type="text" label="Satker" value="{{ auth()->user()->satker }}" readonly />
                        <p class="mt-1 text-xs text-zinc-500">Satker terkunci sesuai akun Anda.</p>
                    @endif
                    @error('satkerId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input wire:model="waktuMulai" type="datetime-local" label="Waktu Mulai" />
                    @error('waktuMulai') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input wire:model="waktuSelesai" type="datetime-local" label="Waktu Selesai" />
                    @error('waktuSelesai') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input wire:model="lokasi" type="text" label="Lokasi" placeholder="Contoh: Posko Delta" />
                    @error('lokasi') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Hasil</label>
                    <flux:select wire:model="hasil">
                        <flux:select.option value="">Belum Ditentukan</flux:select.option>
                        <flux:select.option value="berhasil">Berhasil</flux:select.option>
                        <flux:select.option value="gagal">Gagal</flux:select.option>
                        <flux:select.option value="sebagian">Sebagian</flux:select.option>
                    </flux:select>
                    @error('hasil') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Masalah Misi Terkait (opsional)</label>
                <flux:select wire:model="missionIssueId">
                    <flux:select.option value="">— Tidak ada —</flux:select.option>
                    @foreach ($this->missionIssueOptions as $mi)
                        <flux:select.option :value="$mi->id">#{{ $mi->id }} — {{ $mi->jenis }} ({{ $mi->tanggal?->format('d-m-Y') }})</flux:select.option>
                    @endforeach
                </flux:select>
                @error('missionIssueId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Tujuan Operasi</label>
                <textarea wire:model="tujuanOperasi" rows="3" placeholder="Jelaskan tujuan penggunaan alat..." class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                @error('tujuanOperasi') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Catatan</label>
                <textarea wire:model="catatan" rows="3" placeholder="Catatan tambahan (opsional)..." class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                @error('catatan') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Foto Bukti</label>
                <input wire:model="foto" type="file" accept="image/*" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" />
                <p class="mt-1 text-xs text-zinc-500">Format: jpg, jpeg, png, webp. Maksimal 10MB.</p>
                @error('foto') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            @if ($foto)
                <div>
                    <p class="mb-1 text-xs uppercase text-zinc-500">Preview Foto</p>
                    <img src="{{ $foto->temporaryUrl() }}" alt="Preview foto bukti" class="max-h-48 rounded-lg border border-zinc-200 dark:border-zinc-700">
                </div>
            @endif

            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary">Simpan Data</flux:button>
                <flux:button :href="route('operasi-alat')" wire:navigate variant="ghost" type="button">Batal</flux:button>
            </div>
        </form>
    </section>
</div>
