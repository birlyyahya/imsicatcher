<?php

use App\Models\Satker;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

new #[Layout('layouts.app'), Title('Manajemen User')] class extends Component
{
    public string $search = '';
    public int $perPage = 10;

    public ?int $editingUserId = null;
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $role = 'operator';
    public string $satker = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected array $rules = [
        'name' => 'required|string|max:255',
        'username' => 'required|string|max:255|unique:users,username',
        'email' => 'required|email|max:255|unique:users,email',
        'role' => 'required|in:operator,admin,superadmin',
        'satker' => 'required|string|max:255',
        'password' => 'required|string|min:8|confirmed',
    ];

    protected $messages = [
        'username.unique' => 'Username sudah digunakan.',
        'email.unique' => 'Email sudah digunakan.',
        'role.in' => 'Role tidak valid.',
    ];

    public function mount(): void
    {
        // Hanya superadmin (Kejagung) & admin (Kejati) yang boleh mengelola user.
        abort_if(auth()->user()->isOperator(), 403);

        $this->resetForm();
    }

    /**
     * Batasi daftar user sesuai role: superadmin melihat semua,
     * admin hanya user di satkernya sendiri.
     */
    private function scopedUsers()
    {
        return User::query()
            ->when(auth()->user()->isAdmin(), fn ($query) => $query->where('satker', auth()->user()->satker));
    }

    public function getUsersProperty()
    {
        return $this->scopedUsers()
            ->when($this->search, fn ($query) => $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('username', 'like', "%{$this->search}%")
                  ->orWhere('role', 'like', "%{$this->search}%")
                  ->orWhere('satker', 'like', "%{$this->search}%");
            }))
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function satkerOptions(): array
    {
        return Satker::options();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => $this->scopedUsers()->count(),
            'operator' => $this->scopedUsers()->where('role', 'operator')->count(),
            'admin' => $this->scopedUsers()->where('role', 'admin')->count(),
            'superadmin' => $this->scopedUsers()->where('role', 'superadmin')->count(),
        ];
    }

    public function edit(User $user): void
    {
        abort_unless($this->canManage($user), 403);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->role = $user->role ?? 'operator';
        $this->satker = $user->satker ?? '';
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    /**
     * Admin (Kejati) hanya boleh mengelola operator di satkernya sendiri.
     * Superadmin boleh mengelola siapa saja.
     */
    private function canManage(User $user): bool
    {
        if (auth()->user()->isSuperadmin()) {
            return true;
        }

        return auth()->user()->isAdmin()
            && $user->role === 'operator'
            && $user->satker === auth()->user()->satker;
    }

    public function saveUser(): void
    {
        // Admin terkunci: hanya boleh membuat/mengubah operator di satkernya sendiri.
        if (auth()->user()->isAdmin()) {
            $this->role = 'operator';
            $this->satker = auth()->user()->satker ?? '';
        }

        if ($this->editingUserId) {
            abort_unless($this->canManage(User::findOrFail($this->editingUserId)), 403);
        }

        $rules = $this->rules;

        if ($this->editingUserId) {
            $rules['username'] = "required|string|max:255|unique:users,username,{$this->editingUserId}";
            $rules['email'] = "required|email|max:255|unique:users,email,{$this->editingUserId}";
            if ($this->password === '') {
                unset($rules['password']);
                unset($rules['password_confirmation']);
            }
        }

        $data = $this->validate($rules);

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->name = $data['name'];
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->role = $data['role'];
            $user->satker = $data['satker'];
            if (!empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();

            Toaster::success('User berhasil diperbarui!'); // Placeholder message
        } else {
            User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'role' => $data['role'],
                'satker' => $data['satker'],
                'password' => $data['password'],
            ]);

            Toaster::success('User berhasil ditambahkan!'); // Placeholder message
        }

        $this->resetForm();
    }

    public function deleteUser(User $user): void
    {
        abort_unless($this->canManage($user), 403);

        if (auth()->id() === $user->id) {
            Toaster::error('User sedang digunakan!'); // Placeholder message
            return;
        }

        $user->delete();
        Toaster::success('User berhasil dihapus!'); // Placeholder message
    }

    private function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->username = '';
        $this->email = '';
        $this->role = 'operator';
        // Admin terkunci pada satkernya sendiri.
        $this->satker = auth()->user()->isAdmin() ? (auth()->user()->satker ?? '') : '';
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function render(): View
    {
        return view('pages.management.users');
    }
}
?>
<section class="space-y-6">

    {{-- HEADER --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="xl">Manajemen User</flux:heading>
        <flux:text class="text-sm text-zinc-500">Kelola user dengan cepat dan efisien</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase text-zinc-500">Total User</div>
            <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->stats['total']) }}</div>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase text-zinc-500">Operator</div>
            <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->stats['operator']) }}</div>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase text-zinc-500">Admin</div>
            <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->stats['admin']) }}</div>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase text-zinc-500">Superadmin</div>
            <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($this->stats['superadmin']) }}</div>
        </div>
    </div>

    {{-- MAIN --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- FORM --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">
                    {{ $editingUserId ? 'Edit User' : 'Tambah User' }}
                </flux:heading>

                <form wire:submit.prevent="saveUser" class="space-y-4">

                    <div>
                        <flux:input wire:model="name" type="text" label="Nama" placeholder="Nama user" />
                        @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="username" type="text" label="Username" placeholder="username" />
                        @error('username') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="email" type="email" label="Email" placeholder="user@email.com" />
                        @error('email') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    @if (auth()->user()->isSuperadmin())
                        <div>
                            <label class="text-xs text-zinc-500">Role</label>
                            <flux:select wire:model="role">
                                <flux:select.option value="operator">Operator</flux:select.option>
                                <flux:select.option value="admin">Admin</flux:select.option>
                                <flux:select.option value="superadmin">Superadmin</flux:select.option>
                            </flux:select>
                            @error('role') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div>
                            <label class="text-xs text-zinc-500">Role</label>
                            <flux:input value="Operator" type="text" readonly />
                            <p class="mt-1 text-xs text-zinc-500">Admin hanya dapat menambah operator.</p>
                        </div>
                    @endif

                    <div>
                        @if (auth()->user()->isSuperadmin())
                            <label class="text-xs text-zinc-500">Satker</label>
                            <flux:select wire:model="satker">
                                <flux:select.option value="">Pilih Satker</flux:select.option>
                                @foreach ($this->satkerOptions as $satkerOption)
                                    <flux:select.option :value="$satkerOption">{{ $satkerOption }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:input wire:model="satker" type="text" label="Satker" readonly />
                            <p class="mt-1 text-xs text-zinc-500">Satker terkunci sesuai akun Anda.</p>
                        @endif
                        @error('satker') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="password" type="password" :label="'Password '.($editingUserId ? '(opsional)' : '')" placeholder="Minimal 8 karakter" />
                        @error('password') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="password_confirmation" type="password" label="Konfirmasi Password" placeholder="Ulangi password" />
                    </div>

                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary" class="w-full">
                            {{ $editingUserId ? 'Update' : 'Simpan' }}
                        </flux:button>

                        @if($editingUserId)
                        <flux:button type="button" wire:click="cancelEdit" variant="ghost" class="w-full">
                            Batal
                        </flux:button>
                        @endif
                    </div>

                    <x-action-message on="user-saved" class="text-green-600 text-sm">
                        Berhasil disimpan
                    </x-action-message>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

                {{-- SEARCH --}}
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">Daftar User</flux:heading>

                    <flux:input wire:model.live="search" type="text" class="!w-52" placeholder="Cari..." />
                </div>

                {{-- TABLE --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            <tr>
                                <th class="px-3 py-2 text-left">Nama</th>
                                <th class="px-3 py-2 text-left">Username</th>
                                <th class="px-3 py-2 text-left">Role</th>
                                <th class="px-3 py-2 text-left">Satker</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($this->users as $user)
                            <tr class="border-b border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                <td class="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</td>
                                <td class="px-3 py-2 text-zinc-500 dark:text-zinc-400">{{ $user->username }}</td>
                                <td class="px-3 py-2">
                                    @if(($user->role ?? '') === 'superadmin')
                                        <flux:badge color="green" size="sm">Superadmin</flux:badge>
                                    @elseif(($user->role ?? '') === 'admin')
                                        <flux:badge color="yellow" size="sm">Admin</flux:badge>
                                    @else
                                        <flux:badge color="blue" size="sm">Operator</flux:badge>
                                    @endif
                                </td>
                                <td class="px-3 py-2 max-w-[200px]">
                                    <p class="line-clamp-2 text-zinc-700 dark:text-zinc-300">
                                        {{ $user->satker ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-3 py-2 text-right space-x-2">

                                    <flux:button size="sm" wire:click="edit({{ $user->id }})" type="button" variant="ghost" class="!px-2 !py-1 text-blue-600">
                                        Edit
                                    </flux:button>

                                    <flux:button size="xs" wire:click="deleteUser({{ $user->id }})" type="button" variant="danger" onclick="confirm('Hapus user ini?') || event.stopImmediatePropagation()" class="!px-2 !py-1">
                                        Hapus
                                    </flux:button>

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-zinc-500">
                                    Tidak ada data user
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                <div class="mt-4">
                    {{ $this->users->links() }}
                </div>

            </div>
        </div>

    </div>
</section>
