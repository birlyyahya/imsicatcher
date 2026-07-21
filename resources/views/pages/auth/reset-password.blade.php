<x-layouts::auth.terminal
    :title="__('Setel ulang passphrase')"
    scope-tag="Credential Reset"
    scope-desc="Terbitkan passphrase baru untuk akun operator"
>
    <p class="eyebrow">// SETEL ULANG</p>
    <h1 class="access-title">Setel Ulang Passphrase</h1>
    <p class="access-sub">Buat passphrase baru untuk akun Anda.</p>

    @if (session('status'))
        <div class="alert alert-ok" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert" role="alert">
            <span class="alert-tag">GAGAL</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="form">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <label class="field" for="email">
            <span class="field-label">Email</span>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ request('email') }}"
                required
                autocomplete="email"
                class="field-input"
            >
        </label>

        <label class="field" for="password">
            <span class="field-label">Passphrase Baru</span>
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

        <button type="submit" class="submit" data-test="reset-password-button">
            <span>Setel Ulang Passphrase</span>
        </button>
    </form>

    <p class="foot-note">AKTIVITAS SESI DIREKAM · <b>IMSI CATCHER</b></p>
</x-layouts::auth.terminal>
