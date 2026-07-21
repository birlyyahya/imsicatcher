<x-layouts::auth.terminal
    :title="__('Lupa passphrase')"
    scope-tag="Access Recovery"
    scope-desc="Pulihkan akses ke terminal operasi"
>
    <p class="eyebrow">// PEMULIHAN AKSES</p>
    <h1 class="access-title">Lupa Passphrase</h1>
    <p class="access-sub">Masukkan email terdaftar untuk menerima tautan reset passphrase.</p>

    @if (session('status'))
        <div class="alert alert-ok" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert" role="alert">
            <span class="alert-tag">GAGAL</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="form">
        @csrf

        <label class="field" for="email">
            <span class="field-label">Alamat Email</span>
            <input
                id="email"
                name="email"
                type="email"
                required
                autofocus
                placeholder="operator@kejaksaan.go.id"
                class="field-input"
            >
        </label>

        <button type="submit" class="submit" data-test="email-password-reset-link-button">
            <span>Kirim Tautan Reset</span>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M4 10h11m0 0-4-4m4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>

    <p class="back-link">Sudah ingat passphrase? <a href="{{ route('login') }}" wire:navigate>Kembali masuk</a></p>
</x-layouts::auth.terminal>
