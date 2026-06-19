<?php

use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app'), Title('Kelola FAQ')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $editingId = null;
    public string $question = '';
    public string $answer = '';
    public string $category = '';
    public bool $isPublished = true;
    public int $sortOrder = 0;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function faqs()
    {
        return Faq::query()
            ->when($this->search !== '', function (Builder $query) {
                $search = $this->search;
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('question', 'like', "%{$search}%")
                        ->orWhere('answer', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'isPublished' => 'boolean',
            'sortOrder' => 'nullable|integer|min:0',
        ]);

        $payload = [
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'] ?: null,
            'is_published' => (bool) $validated['isPublished'],
            'sort_order' => (int) ($validated['sortOrder'] ?? 0),
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            Faq::query()->findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'FAQ berhasil diperbarui.');
        } else {
            $payload['created_by'] = auth()->id();
            Faq::query()->create($payload);
            session()->flash('success', 'FAQ berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $item = Faq::query()->findOrFail($id);
        $this->editingId = $item->id;
        $this->question = $item->question;
        $this->answer = $item->answer;
        $this->category = $item->category ?? '';
        $this->isPublished = (bool) $item->is_published;
        $this->sortOrder = (int) $item->sort_order;
    }

    public function delete(int $id): void
    {
        Faq::query()->findOrFail($id)->delete();
        session()->flash('success', 'FAQ berhasil dihapus.');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->question = '';
        $this->answer = '';
        $this->category = '';
        $this->isPublished = true;
        $this->sortOrder = 0;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('pages.documentation.faqs');
    }
};

?>
<section class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Kelola FAQ</flux:heading>
                <flux:text class="text-sm text-zinc-500">CRUD pertanyaan dan jawaban support.</flux:text>
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
            <flux:heading size="lg" class="mb-4">{{ $editingId ? 'Edit FAQ' : 'Tambah FAQ' }}</flux:heading>
            <form wire:submit="save" class="space-y-3">
                <flux:input wire:model="question" label="Pertanyaan" />
                <div>
                    <label class="mb-1 block text-sm font-medium">Jawaban</label>
                    <textarea wire:model="answer" rows="4" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                </div>
                <flux:input wire:model="category" label="Kategori" />
                <flux:input wire:model="sortOrder" type="number" label="Urutan" />
                <label class="text-xs text-zinc-500">Status</label>
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
            </form>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between gap-3">
                <flux:heading size="lg">Daftar FAQ</flux:heading>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari pertanyaan/jawaban..." class="!w-64" />
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 text-left text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th class="px-3 py-2">Pertanyaan</th>
                            <th class="px-3 py-2">Kategori</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($this->faqs as $item)
                            <tr>
                                <td class="px-3 py-2">{{ $item->question }}</td>
                                <td class="px-3 py-2">{{ $item->category ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $item->is_published ? 'Published' : 'Draft' }}</td>
                                <td class="px-3 py-2 text-right space-x-2">
                                    <flux:button wire:click="edit({{ $item->id }})" variant="ghost" size="sm">Edit</flux:button>
                                    <flux:button wire:click="delete({{ $item->id }})" onclick="confirm('Hapus FAQ ini?') || event.stopImmediatePropagation()" variant="danger" size="sm">Hapus</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-zinc-500">Belum ada FAQ.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->faqs->links() }}</div>
        </div>
    </div>
</section>

