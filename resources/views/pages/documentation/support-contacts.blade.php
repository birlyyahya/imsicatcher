<?php

use App\Models\SupportContact;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app'), Title('Kelola Support Contacts')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $editingId = null;
    public string $type = 'email';
    public string $label = '';
    public string $contactValue = '';
    public string $contactUrl = '';
    public string $notes = '';
    public bool $isActive = true;
    public int $sortOrder = 0;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function contacts()
    {
        return SupportContact::query()
            ->when($this->search !== '', function (Builder $query) {
                $search = $this->search;
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('label', 'like', "%{$search}%")
                        ->orWhere('contact_value', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'type' => 'required|in:email,hotline,internal_group,other',
            'label' => 'required|string|max:255',
            'contactValue' => 'required|string|max:255',
            'contactUrl' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'isActive' => 'boolean',
            'sortOrder' => 'nullable|integer|min:0',
        ]);

        $payload = [
            'type' => $validated['type'],
            'label' => $validated['label'],
            'contact_value' => $validated['contactValue'],
            'contact_url' => $validated['contactUrl'] ?: null,
            'notes' => $validated['notes'] ?: null,
            'is_active' => (bool) $validated['isActive'],
            'sort_order' => (int) ($validated['sortOrder'] ?? 0),
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            SupportContact::query()->findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Kontak support berhasil diperbarui.');
        } else {
            $payload['created_by'] = auth()->id();
            SupportContact::query()->create($payload);
            session()->flash('success', 'Kontak support berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $item = SupportContact::query()->findOrFail($id);
        $this->editingId = $item->id;
        $this->type = $item->type;
        $this->label = $item->label;
        $this->contactValue = $item->contact_value;
        $this->contactUrl = $item->contact_url ?? '';
        $this->notes = $item->notes ?? '';
        $this->isActive = (bool) $item->is_active;
        $this->sortOrder = (int) $item->sort_order;
    }

    public function delete(int $id): void
    {
        SupportContact::query()->findOrFail($id)->delete();
        session()->flash('success', 'Kontak support berhasil dihapus.');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->type = 'email';
        $this->label = '';
        $this->contactValue = '';
        $this->contactUrl = '';
        $this->notes = '';
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('pages.documentation.support-contacts');
    }
};

?>
<section class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Kelola Support Contacts</flux:heading>
                <flux:text class="text-sm text-zinc-500">CRUD email, hotline, dan internal group.</flux:text>
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
            <flux:heading size="lg" class="mb-4">{{ $editingId ? 'Edit Kontak' : 'Tambah Kontak' }}</flux:heading>
            <form wire:submit="save" class="space-y-3">
                <label class="text-xs text-zinc-500">Tipe Kontak</label>
                <flux:select wire:model="type">
                    <flux:select.option value="email">Email</flux:select.option>
                    <flux:select.option value="hotline">Hotline</flux:select.option>
                    <flux:select.option value="internal_group">Internal Group</flux:select.option>
                    <flux:select.option value="other">Other</flux:select.option>
                </flux:select>
                <flux:input wire:model="label" label="Label" />
                <flux:input wire:model="contactValue" label="Contact Value" />
                <flux:input wire:model="contactUrl" label="Contact URL" />
                <div>
                    <label class="mb-1 block text-sm font-medium">Catatan</label>
                    <textarea wire:model="notes" rows="3" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                </div>
                <flux:input wire:model="sortOrder" type="number" label="Urutan" />
                <label class="text-xs text-zinc-500">Status</label>
                <flux:select wire:model="isActive">
                    <flux:select.option value="1">Aktif</flux:select.option>
                    <flux:select.option value="0">Nonaktif</flux:select.option>
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
                <flux:heading size="lg">Daftar Kontak Support</flux:heading>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari label/kontak/tipe..." class="!w-64" />
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 text-left text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th class="px-3 py-2">Tipe</th>
                            <th class="px-3 py-2">Label</th>
                            <th class="px-3 py-2">Kontak</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($this->contacts as $item)
                            <tr>
                                <td class="px-3 py-2">{{ strtoupper(str_replace('_', ' ', $item->type)) }}</td>
                                <td class="px-3 py-2">{{ $item->label }}</td>
                                <td class="px-3 py-2">{{ $item->contact_value }}</td>
                                <td class="px-3 py-2">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                <td class="px-3 py-2 text-right space-x-2">
                                    <flux:button wire:click="edit({{ $item->id }})" variant="ghost" size="sm">Edit</flux:button>
                                    <flux:button wire:click="delete({{ $item->id }})" onclick="confirm('Hapus kontak ini?') || event.stopImmediatePropagation()" variant="danger" size="sm">Hapus</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-zinc-500">Belum ada kontak support.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->contacts->links() }}</div>
        </div>
    </div>
</section>

