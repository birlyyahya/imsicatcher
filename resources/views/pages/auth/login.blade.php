<x-layouts::auth.terminal :title="__('Masuk')">
    <p class="eyebrow">// AKSES TERBATAS</p>
    <h1 class="access-title">Terminal</h1>
    <p class="access-sub">Masukkan kredensial operator untuk membuka kanal aman.</p>

    @if ($errors->any())
        <div class="alert" role="alert">
            <span class="alert-tag">DITOLAK</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-ok" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="form">
        @csrf

        <label class="field" for="username">
            <span class="field-label">ID Operator</span>
            <input
                id="username"
                name="username"
                type="text"
                value="{{ old('username') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="operator_id"
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
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="field-input"
                >
                <button type="button" id="pwBtn" class="pw-toggle" onclick="termTogglePw()">LIHAT</button>
            </span>
            @if (Route::has('password.request'))
                <a class="field-aux" href="{{ route('password.request') }}">Lupa passphrase?</a>
            @endif
        </label>

        <label class="remember">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Ingat perangkat ini</span>
        </label>

        <button type="submit" class="submit" data-test="login-button">
            <span>Masuk</span>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M4 10h11m0 0-4-4m4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>

    <p class="foot-note">AKTIVITAS SESI DIREKAM · <b>IMSI CATCHER</b></p>
</x-layouts::auth.terminal>
