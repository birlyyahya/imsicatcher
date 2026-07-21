<x-layouts::auth.terminal
    :title="__('Registrasi operator')"
    scope-tag="Operator Enrollment"
    scope-desc="Ajukan akses baru ke terminal operasi"
>
    <p class="eyebrow">// REGISTRASI OPERATOR</p>
    <h1 class="access-title">Buat Akun Operator</h1>
    <p class="access-sub">Lengkapi data berikut untuk mengajukan akses terminal.</p>

    @if (session('status'))
        <div class="alert alert-ok" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert" role="alert">
            <span class="alert-tag">GAGAL</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store') }}" class="form">
        @csrf

        <label class="field" for="name">
            <span class="field-label">Nama Lengkap</span>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Nama lengkap"
                class="field-input"
            >
        </label>

        <label class="field" for="username">
            <span class="field-label">ID Operator</span>
            <input
                id="username"
                name="username"
                type="text"
                value="{{ old('username') }}"
                required
                autocomplete="username"
                placeholder="operator_id"
                class="field-input"
            >
        </label>

        <label class="field" for="email">
            <span class="field-label">Alamat Email</span>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                placeholder="operator@kejaksaan.go.id"
                class="field-input"
            >
        </label>

        <label class="field" for="password">
            <span class="field-label">Passphrase</span>
            <span class="field-wrap">
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="field-input"
                >
                <button type="button" id="pwBtn" class="pw-toggle" onclick="termTogglePw()">LIHAT</button>
            </span>
        </label>

        <label class="field" for="password_confirmation">
            <span class="field-label">Konfirmasi Passphrase</span>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                placeholder="••••••••"
                class="field-input"
            >
        </label>

        <button type="submit" class="submit" data-test="register-user-button">
            <span>Ajukan Akses</span>
        </button>
    </form>

    <p class="back-link">Sudah punya akun? <a href="{{ route('login') }}" wire:navigate>Masuk</a></p>
</x-layouts::auth.terminal>
