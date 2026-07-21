<x-layouts::auth.terminal
    :title="__('Konfirmasi akses')"
    scope-tag="Re-Authentication Checkpoint"
    scope-desc="Konfirmasi identitas sebelum mengakses data sensitif"
>
    <p class="eyebrow">// RE-OTENTIKASI</p>
    <h1 class="access-title">Konfirmasi Akses</h1>
    <p class="access-sub">Area ini memerlukan verifikasi ulang passphrase Anda sebelum melanjutkan.</p>

    @if (session('status'))
        <div class="alert alert-ok" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert" role="alert">
            <span class="alert-tag">DITOLAK</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm.store') }}" class="form">
        @csrf

        <label class="field" for="password">
            <span class="field-label">Passphrase</span>
            <span class="field-wrap">
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="field-input"
                >
                <button type="button" id="pwBtn" class="pw-toggle" onclick="termTogglePw()">LIHAT</button>
            </span>
        </label>

        <button type="submit" class="submit" data-test="confirm-password-button">
            <span>Konfirmasi</span>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M4 10h11m0 0-4-4m4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>

    <p class="foot-note">AKTIVITAS SESI DIREKAM · <b>IMSI CATCHER</b></p>
</x-layouts::auth.terminal>
