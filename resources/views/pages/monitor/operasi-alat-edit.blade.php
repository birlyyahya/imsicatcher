<?php

use App\Models\MissionIssue;
use App\Models\OperasiAlat;
use App\Models\Satker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Masmerise\Toaster\Toaster;

new #[Layout('layouts.app'), Title('Edit Log Operasi Alat')] class extends Component
{
    use WithFileUploads;

    public OperasiAlat $operasiAlat;

    public string $jenisAlat = '';
    public ?int $satkerId = null;
    public string $waktuMulai = '';
    public ?string $waktuSelesai = '';
    public ?string $latitude = null;
    public ?string $longitude = null;
    public ?string $lokasiKeterangan = '';
    public string $tujuanOperasi = '';
    public ?string $hasil = '';
    public ?int $missionIssueId = null;
    public ?string $catatan = '';
    public mixed $foto = null;
    public ?string $existingFotoBukti = null;

    // Sub-form "Buat Masalah Misi" inline (langsung tersimpan & tersambung).
    public bool $showMissionIssueForm = false;
    public string $miTanggal = '';
    public string $miLokasi = '';
    public string $miJenis = '';
    public string $miDeskripsi = '';
    public string $miPelapor = '';
    public string $miSatker = '';
    public string $miStatus = 'baru';

    public function mount(OperasiAlat $operasiAlat): void
    {
        abort_unless($operasiAlat->isManageableBy(auth()->user()), 403);

        $this->operasiAlat = $operasiAlat;
        $this->jenisAlat = $operasiAlat->jenis_alat;
        $this->satkerId = $operasiAlat->satker_id;
        $this->waktuMulai = optional($operasiAlat->waktu_mulai)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->waktuSelesai = optional($operasiAlat->waktu_selesai)->format('Y-m-d\TH:i') ?? '';
        $this->latitude = $operasiAlat->latitude;
        $this->longitude = $operasiAlat->longitude;
        $this->lokasiKeterangan = $operasiAlat->lokasi_keterangan ?? '';
        $this->tujuanOperasi = $operasiAlat->tujuan_operasi;
        $this->hasil = $operasiAlat->hasil ?? '';
        $this->missionIssueId = $operasiAlat->mission_issue_id;
        $this->catatan = $operasiAlat->catatan ?? '';
        $this->existingFotoBukti = $operasiAlat->foto_bukti;
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

    public function toggleMissionIssueForm(): void
    {
        $this->showMissionIssueForm = ! $this->showMissionIssueForm;

        if ($this->showMissionIssueForm) {
            $this->resetMissionIssueForm();
        } else {
            $this->resetValidation();
        }
    }

    private function resetMissionIssueForm(): void
    {
        $this->miTanggal = now()->format('Y-m-d\TH:i');
        $this->miLokasi = '';
        $this->miJenis = '';
        $this->miDeskripsi = '';
        $this->miPelapor = '';
        $this->miSatker = '';
        $this->miStatus = 'baru';
    }

    /**
     * Buat MissionIssue baru langsung dari form ini lalu sambungkan ke log operasi alat.
     *
     * Record disimpan permanen seketika (independen dari submit form utama) supaya
     * data Masalah Misi tidak hilang walau form operasi alat masih draft.
     */
    public function createMissionIssue(): void
    {
        // Kunci satker di sisi server (pola lock satker di mission-issues-create):
        // operator/admin terkunci pada satkernya; superadmin mengikuti satker yang
        // dipilih pada form operasi alat di atas. Ini menjamin operator hanya bisa
        // membuat Masalah Misi untuk satkernya sendiri.
        if (auth()->user()->isSuperadmin()) {
            $this->miSatker = $this->satkerId
                ? (Satker::query()->whereKey($this->satkerId)->value('nama') ?? '')
                : '';
        } else {
            $this->miSatker = auth()->user()->satker ?? '';
        }

        // Rules identik dengan mission-issues-create untuk field-field ini.
        $validated = $this->validate([
            'miTanggal' => 'required|date',
            'miLokasi' => 'required|string|max:255',
            'miJenis' => 'required|string|max:255',
            'miDeskripsi' => 'required|string',
            'miPelapor' => 'required|string|max:255',
            'miSatker' => 'required|string|max:255',
            'miStatus' => 'required|in:baru,proses,selesai',
        ]);

        $issue = MissionIssue::query()->create([
            'tanggal' => $validated['miTanggal'],
            'lokasi' => $validated['miLokasi'],
            'jenis' => $validated['miJenis'],
            'deskripsi' => $validated['miDeskripsi'],
            'pelapor' => $validated['miPelapor'],
            'satker' => $validated['miSatker'],
            'status' => $validated['miStatus'],
            'dibuat_oleh' => auth()->id(),
        ]);

        // Sambungkan ke form operasi alat (berlaku saat create maupun edit existing).
        $this->missionIssueId = $issue->id;

        // Refresh dropdown supaya issue baru langsung muncul & terpilih.
        unset($this->missionIssueOptions);

        $this->showMissionIssueForm = false;
        $this->resetMissionIssueForm();

        Toaster::success('Incident baru berhasil dibuat dan disambungkan');
    }

    protected function rules(): array
    {
        return [
            'jenisAlat' => 'required|in:stingray_interceptor,hhdf,drone,laptop_operasi',
            'satkerId' => 'required|exists:satkers,id',
            'waktuMulai' => 'required|date',
            'waktuSelesai' => 'nullable|date|after_or_equal:waktuMulai',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'lokasiKeterangan' => 'nullable|string|max:255',
            'tujuanOperasi' => 'required|string',
            'hasil' => 'nullable|in:berhasil,gagal,sebagian',
            'missionIssueId' => 'nullable|integer|exists:mission_issues,id',
            'catatan' => 'nullable|string',
            'foto' => 'nullable|image|max:10240',
        ];
    }

    public function save(): void
    {
        abort_unless($this->operasiAlat->isManageableBy(auth()->user()), 403);

        // Kunci satker di sisi server agar tidak bisa diubah lewat request.
        if (! auth()->user()->isSuperadmin()) {
            $this->satkerId = auth()->user()->satker_id;
        }

        // Normalisasi string kosong dari <select>/<input> menjadi null sebelum validasi.
        foreach (['waktuSelesai', 'latitude', 'longitude', 'lokasiKeterangan', 'hasil', 'catatan'] as $field) {
            if ($this->{$field} === '') {
                $this->{$field} = null;
            }
        }

        $validated = $this->validate();

        $payload = [
            'jenis_alat' => $validated['jenisAlat'],
            'satker_id' => $validated['satkerId'],
            'waktu_mulai' => $validated['waktuMulai'],
            'waktu_selesai' => $validated['waktuSelesai'] ?: null,
            'latitude' => $validated['latitude'] ?: null,
            'longitude' => $validated['longitude'] ?: null,
            'lokasi_keterangan' => $validated['lokasiKeterangan'] ?: null,
            'tujuan_operasi' => $validated['tujuanOperasi'],
            'hasil' => $validated['hasil'] ?: null,
            'mission_issue_id' => $validated['missionIssueId'] ?: null,
            'catatan' => $validated['catatan'] ?: null,
        ];

        if ($this->foto) {
            if ($this->existingFotoBukti && Storage::disk('public')->exists($this->existingFotoBukti)) {
                Storage::disk('public')->delete($this->existingFotoBukti);
            }

            $payload['foto_bukti'] = $this->foto->store('operasi-alat', 'public');
        }

        $this->operasiAlat->update($payload);

        Toaster::success('Log operasi alat berhasil diperbarui.');
        $this->redirectRoute('operasi-alat', navigate: true);
    }

    public function render(): View
    {
        return view('pages.monitor.operasi-alat-edit');
    }
};

?>
@assets
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@endassets
<div class="space-y-6">
    <header class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-2">
            <div>
                <flux:heading size="xl">Edit Log Operasi Alat #{{ $operasiAlat->id }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Perbarui data log operasi alat.</flux:text>
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

            <div class="space-y-3">
                <label class="block text-sm font-medium">Lokasi Operasi</label>
                @include('partials.operasi-alat-map', ['interactive' => true, 'latitude' => $latitude, 'longitude' => $longitude])
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <flux:input wire:model.blur="latitude" type="text" inputmode="decimal" label="Latitude" placeholder="-0.8615 (klik peta / isi manual)" />
                        @error('latitude') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:input wire:model.blur="longitude" type="text" inputmode="decimal" label="Longitude" placeholder="134.0620 (klik peta / isi manual)" />
                        @error('longitude') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:input wire:model="lokasiKeterangan" type="text" label="Keterangan Lokasi" placeholder="Contoh: Ruang Server 2" />
                        @error('lokasiKeterangan') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between gap-2">
                    <label class="block text-sm font-medium">Incident Terkait (opsional)</label>
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="toggleMissionIssueForm">
                        {{ $showMissionIssueForm ? 'Tutup' : 'Buat Incident Baru' }}
                    </flux:button>
                </div>
                <flux:select wire:model="missionIssueId">
                    <flux:select.option value="">— Tidak ada —</flux:select.option>
                    @foreach ($this->missionIssueOptions as $mi)
                        <flux:select.option :value="$mi->id">#{{ $mi->id }} — {{ $mi->jenis }} ({{ $mi->tanggal?->format('d-m-Y') }})</flux:select.option>
                    @endforeach
                </flux:select>
                @error('missionIssueId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

                @if ($showMissionIssueForm)
                    @php($lockedSatker = auth()->user()->isSuperadmin() ? ($this->satkerOptions[$satkerId] ?? '') : auth()->user()->satker)
                    <div x-data @keydown.enter.prevent class="mt-3 space-y-3 rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 dark:border-zinc-600 dark:bg-zinc-800/50">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading size="sm">Incident Baru</flux:heading>
                            <flux:text class="text-xs text-zinc-500">Langsung tersimpan & tersambung, walau form alat belum disimpan.</flux:text>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <flux:input wire:model="miTanggal" type="datetime-local" label="Tanggal & Waktu" />
                                @error('miTanggal') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="miLokasi" type="text" label="Lokasi" placeholder="Contoh: Posko Delta" />
                                @error('miLokasi') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="miJenis" type="text" label="Jenis" placeholder="Contoh: Gangguan Jaringan" />
                                @error('miJenis') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="miPelapor" type="text" label="Pelapor" placeholder="Nama pelapor" />
                                @error('miPelapor') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input type="text" label="Satker" value="{{ $lockedSatker }}" readonly />
                                <p class="mt-1 text-xs text-zinc-500">Satker mengikuti pilihan di atas / akun Anda.</p>
                                @error('miSatker') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Status</label>
                                <flux:select wire:model="miStatus">
                                    <flux:select.option value="baru">Baru</flux:select.option>
                                    <flux:select.option value="proses">Dalam Proses</flux:select.option>
                                    <flux:select.option value="selesai">Selesai</flux:select.option>
                                </flux:select>
                                @error('miStatus') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Deskripsi</label>
                            <textarea wire:model="miDeskripsi" rows="3" placeholder="Jelaskan kronologi masalah yang terjadi..." class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            @error('miDeskripsi') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button type="button" size="sm" variant="primary" wire:click="createMissionIssue">Simpan Incident</flux:button>
                            <flux:button type="button" size="sm" variant="ghost" wire:click="toggleMissionIssueForm">Batal</flux:button>
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Tujuan Operasi</label>
                <textarea wire:model="tujuanOperasi" rows="3" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                @error('tujuanOperasi') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Catatan</label>
                <textarea wire:model="catatan" rows="3" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                @error('catatan') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Ganti Foto Bukti</label>
                <input wire:model="foto" type="file" accept="image/*" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" />
                <p class="mt-1 text-xs text-zinc-500">Format: jpg, jpeg, png, webp. Maksimal 10MB.</p>
                @error('foto') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            @if ($foto)
                <div>
                    <p class="mb-1 text-xs uppercase text-zinc-500">Preview Foto Baru</p>
                    <img src="{{ $foto->temporaryUrl() }}" alt="Preview foto bukti baru" class="max-h-48 rounded-lg border border-zinc-200 dark:border-zinc-700">
                </div>
            @elseif ($existingFotoBukti)
                <div>
                    <p class="mb-1 text-xs uppercase text-zinc-500">Foto Saat Ini</p>
                    <img src="{{ Storage::url($existingFotoBukti) }}" alt="Foto bukti saat ini" class="max-h-48 rounded-lg border border-zinc-200 dark:border-zinc-700">
                </div>
            @endif

            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary">Update Data</flux:button>
                <flux:button :href="route('operasi-alat')" wire:navigate variant="ghost" type="button">Batal</flux:button>
            </div>
        </form>
    </section>
</div>
