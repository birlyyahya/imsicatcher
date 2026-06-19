<?php

use App\Models\DocumentationMaterial;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.app'), Title('Kelola Materi')] class extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $editingId = null;
    public string $title = '';
    public string $slug = '';
    public string $description = '';
    public string $materialType = 'video';
    public string $contentUrl = '';
    public string $filePath = '';
    public mixed $materialFile = null;
    public string $durationMinutes = '';
    public string $version = '';
    public bool $isPublished = true;
    public int $sortOrder = 0;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function materials()
    {
        return DocumentationMaterial::query()
            ->when($this->search !== '', function (Builder $query) {
                $search = $this->search;
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('material_type', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate($this->perPage);
    }

    public function updatedTitle(string $value): void
    {
        if (!$this->editingId && $this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:documentation_materials,slug',
            'description' => 'nullable|string',
            'materialType' => 'required|in:video,pdf,checklist,link,other',
            'contentUrl' => 'nullable|url|max:255',
            'filePath' => 'nullable|string|max:255',
            'materialFile' => 'nullable|file|mimes:pdf|max:10240',
            'durationMinutes' => 'nullable|integer|min:0',
            'version' => 'nullable|string|max:50',
            'isPublished' => 'boolean',
            'sortOrder' => 'nullable|integer|min:0',
        ];

        if ($this->editingId) {
            $rules['slug'] = "required|string|max:255|unique:documentation_materials,slug,{$this->editingId}";
        }

        $validated = $this->validate($rules);

        $payload = [
            'title' => $validated['title'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?: null,
            'material_type' => $validated['materialType'],
            'content_url' => $validated['contentUrl'] ?: null,
            'file_path' => $validated['filePath'] ?: null,
            'duration_minutes' => $validated['durationMinutes'] !== '' ? (int) $validated['durationMinutes'] : null,
            'version' => $validated['version'] ?: null,
            'is_published' => (bool) $validated['isPublished'],
            'sort_order' => (int) ($validated['sortOrder'] ?? 0),
            'updated_by' => auth()->id(),
        ];

        if ($this->materialFile) {
            if ($this->editingId && $this->filePath && Storage::disk('public')->exists($this->filePath)) {
                Storage::disk('public')->delete($this->filePath);
            }

            $payload['file_path'] = $this->materialFile->store('materials', 'public');
        }

        if ($validated['materialType'] === 'pdf' && empty($payload['content_url']) && empty($payload['file_path'])) {
            $this->addError('materialFile', 'Untuk materi tipe PDF, isi Content URL atau upload file PDF.');
            return;
        }

        if ($this->editingId) {
            DocumentationMaterial::query()->findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Materi berhasil diperbarui.');
        } else {
            $payload['created_by'] = auth()->id();
            DocumentationMaterial::query()->create($payload);
            session()->flash('success', 'Materi berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $item = DocumentationMaterial::query()->findOrFail($id);
        $this->editingId = $item->id;
        $this->title = $item->title;
        $this->slug = $item->slug;
        $this->description = $item->description ?? '';
        $this->materialType = $item->material_type;
        $this->contentUrl = $item->content_url ?? '';
        $this->filePath = $item->file_path ?? '';
        $this->durationMinutes = $item->duration_minutes !== null ? (string) $item->duration_minutes : '';
        $this->version = $item->version ?? '';
        $this->isPublished = (bool) $item->is_published;
        $this->sortOrder = (int) $item->sort_order;
        $this->materialFile = null;
    }

    public function delete(int $id): void
    {
        $item = DocumentationMaterial::query()->findOrFail($id);

        if ($item->file_path && Storage::disk('public')->exists($item->file_path)) {
            Storage::disk('public')->delete($item->file_path);
        }

        $item->forceDelete();
        session()->flash('success', 'Materi berhasil dihapus.');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->slug = '';
        $this->description = '';
        $this->materialType = 'video';
        $this->contentUrl = '';
        $this->filePath = '';
        $this->materialFile = null;
        $this->durationMinutes = '';
        $this->version = '';
        $this->isPublished = true;
        $this->sortOrder = 0;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('pages.documentation.materials');
    }
};

?>
<section class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Kelola Materi</flux:heading>
                <flux:text class="text-sm text-zinc-500">CRUD materi training/documentation.</flux:text>
            </div>
            <flux:button :href="route('documentation')" wire:navigate variant="ghost">Kembali</flux:button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-1">
            <flux:heading size="lg" class="mb-4">{{ $editingId ? 'Edit Materi' : 'Tambah Materi' }}</flux:heading>
            <form wire:submit="save" class="space-y-3">
                <flux:input wire:model="title" label="Judul" />
                <flux:input wire:model="slug" label="Slug" />
                <div>
                    <label class="mb-1 block text-sm font-medium">Deskripsi</label>
                    <textarea wire:model="description" rows="3" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                </div>
                <label class="text-xs text-zinc-500">Tipe Materi</label>
                <flux:select wire:model="materialType">
                    <flux:select.option value="video">Video</flux:select.option>
                    <flux:select.option value="pdf">PDF</flux:select.option>
                    <flux:select.option value="checklist">Checklist</flux:select.option>
                    <flux:select.option value="link">Link</flux:select.option>
                    <flux:select.option value="other">Other</flux:select.option>
                </flux:select>
                <flux:input wire:model="contentUrl" label="Content URL" />
                <div>
                    <label class="mb-1 block text-sm font-medium">Upload File PDF</label>
                    <input wire:model="materialFile" type="file" accept="application/pdf" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                    <p class="mt-1 text-xs text-zinc-500">Maksimal 10MB. Jika diisi, `filePath` akan terisi otomatis.</p>
                    @error('materialFile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <flux:input wire:model="filePath" label="File Path (otomatis/manual)" />
                <flux:input wire:model="durationMinutes" type="number" label="Durasi (menit)" />
                <flux:input wire:model="version" label="Versi" />
                <flux:input wire:model="sortOrder" type="number" label="Urutan" />
                <label class="text-xs text-zinc-500">Status Publikasi</label>
                <flux:select wire:model="isPublished">
                    <flux:select.option value="1">Published</flux:select.option>
                    <flux:select.option value="0">Draft</flux:select.option>
                </flux:select>
                <div class="flex gap-2 pt-1">
                    <flux:button type="submit" variant="primary" class="w-full">{{ $editingId ? 'Update' : 'Simpan' }}</flux:button>
                    @if ($editingId)
                        <flux:button type="button" wire:click="cancelEdit" variant="ghost" class="w-full">Batal</flux:button>
                    @endif
                </div>

                @if ($materialFile)
                    <div class="rounded-lg border border-zinc-200 p-2 text-xs text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                        File dipilih: {{ $materialFile->getClientOriginalName() }}
                    </div>
                @elseif ($filePath)
                    <div class="rounded-lg border border-zinc-200 p-2 text-xs text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                        File saat ini: {{ $filePath }}
                    </div>
                @endif
            </form>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between gap-3">
                <flux:heading size="lg">Daftar Materi</flux:heading>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari judul/slug/tipe..." class="!w-64" />
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 text-left text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th class="px-3 py-2">Judul</th>
                            <th class="px-3 py-2">Tipe</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Urutan</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($this->materials as $item)
                            <tr>
                                <td class="px-3 py-2">{{ $item->title }}</td>
                                <td class="px-3 py-2">{{ strtoupper($item->material_type) }}</td>
                                <td class="px-3 py-2">{{ $item->is_published ? 'Published' : 'Draft' }}</td>
                                <td class="px-3 py-2">{{ $item->sort_order }}</td>
                                <td class="px-3 py-2 text-right space-x-2">
                                    <flux:button wire:click="edit({{ $item->id }})" variant="ghost" size="sm">Edit</flux:button>
                                    <flux:button wire:click="delete({{ $item->id }})" onclick="confirm('Hapus materi ini?') || event.stopImmediatePropagation()" variant="danger" size="sm">Hapus</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-zinc-500">Belum ada materi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->materials->links() }}</div>
        </div>
    </div>
</section>
