<?php

use App\Models\Aset;
use App\Models\Satker;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

new #[Layout('layouts.app'), Title('Inventaris Aset')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    // Filter
    public string $filterKategori = '';
    public string $filterStatus = '';
    public string $filterSatker = '';

    // Form
    public ?int $editingAsetId = null;
    public string $nama_aset = '';
    public string $kategori = 'server';
    public string $nomor_seri = '';
    public string $status = 'aktif';
    public ?int $satker_id = null;
    public string $tanggal_pengadaan = '';
    public string $catatan = '';

    public function mount(): void
    {
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterKategori(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSatker(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'nama_aset' => 'required|string|max:255',
            'kategori' => 'required|in:'.implode(',', array_keys(Aset::KATEGORI)),
            'nomor_seri' => 'nullable|string|max:255|unique:asets,nomor_seri'
                .($this->editingAsetId ? ",{$this->editingAsetId}" : ''),
            'status' => 'required|in:'.implode(',', array_keys(Aset::STATUS)),
            'satker_id' => 'nullable|exists:satkers,id',
            'tanggal_pengadaan' => 'nullable|date',
            'catatan' => 'nullable|string',
        ];
    }

    protected $messages = [
        'nomor_seri.unique' => 'Nomor seri sudah terdaftar.',
    ];

    #[Computed]
    public function asets()
    {
        return Aset::query()
            ->visibleTo(auth()->user())
            ->with('satker')
            ->when($this->search, fn ($query) => $query->where(function ($q) {
                $q->where('nama_aset', 'like', "%{$this->search}%")
                  ->orWhere('nomor_seri', 'like', "%{$this->search}%");
            }))
            ->when($this->filterKategori, fn ($query) => $query->where('kategori', $this->filterKategori))
            ->when($this->filterStatus, fn ($query) => $query->where('status', $this->filterStatus))
            ->when($this->filterSatker, fn ($query) => $query->where('satker_id', $this->filterSatker))
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function satkerOptions(): array
    {
        return Satker::query()->orderBy('nama')->pluck('nama', 'id')->all();
    }

    #[Computed]
    public function kategoriOptions(): array
    {
        return Aset::KATEGORI;
    }

    #[Computed]
    public function statusOptions(): array
    {
        return Aset::STATUS;
    }

    public function edit(Aset $aset): void
    {
        abort_unless($aset->isManageableBy(auth()->user()), 403);

        $this->editingAsetId = $aset->id;
        $this->nama_aset = $aset->nama_aset;
        $this->kategori = $aset->kategori;
        $this->nomor_seri = $aset->nomor_seri ?? '';
        $this->status = $aset->status;
        $this->satker_id = $aset->satker_id;
        $this->tanggal_pengadaan = $aset->tanggal_pengadaan?->format('Y-m-d') ?? '';
        $this->catatan = $aset->catatan ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function saveAset(): void
    {
        // Admin/operator terkunci pada satkernya sendiri.
        if (! auth()->user()->isSuperadmin()) {
            $this->satker_id = auth()->user()->satker_id;
        }

        if ($this->editingAsetId) {
            abort_unless(
                Aset::findOrFail($this->editingAsetId)->isManageableBy(auth()->user()),
                403
            );
        }

        $data = $this->validate();
        $data['nomor_seri'] = $data['nomor_seri'] ?: null;
        $data['tanggal_pengadaan'] = $data['tanggal_pengadaan'] ?: null;

        if ($this->editingAsetId) {
            Aset::findOrFail($this->editingAsetId)->update($data);
            Toaster::success('Aset berhasil diperbarui!');
        } else {
            Aset::create($data);
            Toaster::success('Aset berhasil ditambahkan!');
        }

        $this->resetForm();
    }

    public function deleteAset(Aset $aset): void
    {
        abort_unless($aset->isManageableBy(auth()->user()), 403);

        $aset->delete();
        Toaster::success('Aset berhasil dihapus!');
    }

    private function resetForm(): void
    {
        $this->editingAsetId = null;
        $this->nama_aset = '';
        $this->kategori = 'server';
        $this->nomor_seri = '';
        $this->status = 'aktif';
        // Admin/operator terkunci pada satkernya sendiri.
        $this->satker_id = auth()->user()->isSuperadmin() ? null : auth()->user()->satker_id;
        $this->tanggal_pengadaan = '';
        $this->catatan = '';
    }

    public function render(): View
    {
        return view('pages.management.aset');
    }
}
?>
<section class="space-y-6">

    {{-- HEADER --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="xl">Inventaris Aset</flux:heading>
        <flux:text class="text-sm text-zinc-500">Kelola master data aset operasional per satuan kerja.</flux:text>
    </div>

    {{-- MAIN --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- FORM --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">
                    {{ $editingAsetId ? 'Edit Aset' : 'Tambah Aset' }}
                </flux:heading>

                <form wire:submit.prevent="saveAset" class="space-y-4">
                    <div>
                        <flux:input wire:model="nama_aset" type="text" label="Nama Aset" placeholder="Contoh: Server Utama A1" />
                        @error('nama_aset') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-xs text-zinc-500">Kategori</label>
                        <flux:select wire:model="kategori">
                            @foreach ($this->kategoriOptions as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('kategori') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="nomor_seri" type="text" label="Nomor Seri (opsional)" placeholder="Nomor seri unik" />
                        @error('nomor_seri') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-xs text-zinc-500">Status</label>
                        <flux:select wire:model="status">
                            @foreach ($this->statusOptions as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('status') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        @if (auth()->user()->isSuperadmin())
                            <label class="text-xs text-zinc-500">Satker</label>
                            <flux:select wire:model="satker_id">
                                <flux:select.option value="">Pilih Satker</flux:select.option>
                                @foreach ($this->satkerOptions as $id => $nama)
                                    <flux:select.option :value="$id">{{ $nama }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:input :value="$this->satkerOptions[auth()->user()->satker_id] ?? '-'" type="text" label="Satker" readonly />
                            <p class="mt-1 text-xs text-zinc-500">Satker terkunci sesuai akun Anda.</p>
                        @endif
                        @error('satker_id') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="tanggal_pengadaan" type="date" label="Tanggal Pengadaan (opsional)" />
                        @error('tanggal_pengadaan') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Catatan</label>
                        <textarea wire:model="catatan" rows="3" placeholder="Catatan (opsional)" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        @error('catatan') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary" class="w-full">
                            {{ $editingAsetId ? 'Update' : 'Simpan' }}
                        </flux:button>

                        @if($editingAsetId)
                        <flux:button type="button" wire:click="cancelEdit" variant="ghost" class="w-full">
                            Batal
                        </flux:button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">Daftar Aset</flux:heading>
                    <flux:input wire:model.live="search" type="text" class="w-52!" placeholder="Cari nama / nomor seri..." />
                </div>

                {{-- FILTER --}}
                <div class="mb-4 grid gap-2 sm:grid-cols-3">
                    <flux:select wire:model.live="filterKategori" size="sm">
                        <flux:select.option value="">Semua Kategori</flux:select.option>
                        @foreach ($this->kategoriOptions as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterStatus" size="sm">
                        <flux:select.option value="">Semua Status</flux:select.option>
                        @foreach ($this->statusOptions as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    @if (auth()->user()->isSuperadmin())
                        <flux:select wire:model.live="filterSatker" size="sm">
                            <flux:select.option value="">Semua Satker</flux:select.option>
                            @foreach ($this->satkerOptions as $id => $nama)
                                <flux:select.option :value="$id">{{ $nama }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            <tr>
                                <th class="px-3 py-2 text-left">Nama Aset</th>
                                <th class="px-3 py-2 text-left">Kategori</th>
                                <th class="px-3 py-2 text-left">Nomor Seri</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Satker</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($this->asets as $aset)
                            <tr class="border-b border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                <td class="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $aset->nama_aset }}</td>
                                <td class="px-3 py-2 text-zinc-700 dark:text-zinc-300">{{ $aset->kategoriLabel() }}</td>
                                <td class="px-3 py-2 text-zinc-500 dark:text-zinc-400">{{ $aset->nomor_seri ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    @if($aset->status === 'aktif')
                                        <flux:badge color="green" size="sm">Aktif</flux:badge>
                                    @elseif($aset->status === 'maintenance')
                                        <flux:badge color="yellow" size="sm">Maintenance</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm">Rusak</flux:badge>
                                    @endif
                                </td>
                                <td class="px-3 py-2 max-w-50">
                                    <p class="line-clamp-2 text-zinc-700 dark:text-zinc-300">{{ $aset->satker?->nama ?? '-' }}</p>
                                </td>
                                <td class="px-3 py-2 text-right space-x-2">
                                    <flux:button size="sm" wire:click="edit({{ $aset->id }})" type="button" variant="ghost" class="px-2! py-1! text-blue-600">
                                        Edit
                                    </flux:button>
                                    <flux:button size="xs" wire:click="deleteAset({{ $aset->id }})" type="button" variant="danger" onclick="confirm('Hapus aset ini?') || event.stopImmediatePropagation()" class="px-2! py-1!">
                                        Hapus
                                    </flux:button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-zinc-500">Belum ada data aset</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $this->asets->links() }}
                </div>

            </div>
        </div>

    </div>
</section>
