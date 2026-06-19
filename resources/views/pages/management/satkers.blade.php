<?php

use App\Models\Satker;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

new #[Layout('layouts.app'), Title('Manajemen Satker')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $editingSatkerId = null;
    public string $nama = '';
    public string $keterangan = '';

    public function mount(): void
    {
        // Hanya superadmin (Kejagung) yang boleh mengelola data satker.
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255|unique:satkers,nama'
                .($this->editingSatkerId ? ",{$this->editingSatkerId}" : ''),
            'keterangan' => 'nullable|string',
        ];
    }

    protected $messages = [
        'nama.unique' => 'Nama satker sudah terdaftar.',
    ];

    #[Computed]
    public function satkers()
    {
        return Satker::query()
            ->when($this->search, fn ($query) => $query->where('nama', 'like', "%{$this->search}%")
                ->orWhere('keterangan', 'like', "%{$this->search}%"))
            ->orderBy('nama')
            ->paginate($this->perPage);
    }

    public function edit(Satker $satker): void
    {
        $this->editingSatkerId = $satker->id;
        $this->nama = $satker->nama;
        $this->keterangan = $satker->keterangan ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function saveSatker(): void
    {
        $data = $this->validate();

        if ($this->editingSatkerId) {
            Satker::findOrFail($this->editingSatkerId)->update($data);
            Toaster::success('Satker berhasil diperbarui!');
        } else {
            Satker::create($data);
            Toaster::success('Satker berhasil ditambahkan!');
        }

        $this->resetForm();
    }

    public function deleteSatker(Satker $satker): void
    {
        $satker->delete();
        Toaster::success('Satker berhasil dihapus!');
    }

    private function resetForm(): void
    {
        $this->editingSatkerId = null;
        $this->nama = '';
        $this->keterangan = '';
    }

    public function render(): View
    {
        return view('pages.management.satkers');
    }
}
?>
<section class="space-y-6">

    {{-- HEADER --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="xl">Manajemen Satker</flux:heading>
        <flux:text class="text-sm text-zinc-500">Kelola daftar satuan kerja yang digunakan pada data misi dan user.</flux:text>
    </div>

    {{-- MAIN --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- FORM --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">
                    {{ $editingSatkerId ? 'Edit Satker' : 'Tambah Satker' }}
                </flux:heading>

                <form wire:submit.prevent="saveSatker" class="space-y-4">
                    <div>
                        <flux:input wire:model="nama" type="text" label="Nama Satker" placeholder="Contoh: Kejaksaan Tinggi Papua Barat" />
                        @error('nama') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Keterangan</label>
                        <textarea wire:model="keterangan" rows="3" placeholder="Keterangan (opsional)" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        @error('keterangan') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary" class="w-full">
                            {{ $editingSatkerId ? 'Update' : 'Simpan' }}
                        </flux:button>

                        @if($editingSatkerId)
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
                    <flux:heading size="lg">Daftar Satker</flux:heading>
                    <flux:input wire:model.live="search" type="text" class="w-52!" placeholder="Cari..." />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            <tr>
                                <th class="px-3 py-2 text-left">Nama Satker</th>
                                <th class="px-3 py-2 text-left">Keterangan</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($this->satkers as $satker)
                            <tr class="border-b border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                <td class="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $satker->nama }}</td>
                                <td class="px-3 py-2 max-w-50">
                                    <p class="line-clamp-2 text-zinc-700 dark:text-zinc-300">{{ $satker->keterangan ?: '-' }}</p>
                                </td>
                                <td class="px-3 py-2 text-right space-x-2">
                                    <flux:button size="sm" wire:click="edit({{ $satker->id }})" type="button" variant="ghost" class="px-2! py-1! text-blue-600">
                                        Edit
                                    </flux:button>
                                    <flux:button size="xs" wire:click="deleteSatker({{ $satker->id }})" type="button" variant="danger" onclick="confirm('Hapus satker ini?') || event.stopImmediatePropagation()" class="px-2! py-1!">
                                        Hapus
                                    </flux:button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-zinc-500">Belum ada data satker</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $this->satkers->links() }}
                </div>

            </div>
        </div>

    </div>
</section>
