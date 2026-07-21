<x-layouts::auth.terminal
    :title="__('Verifikasi email')"
    scope-tag="Account Verification"
    scope-desc="Aktifkan akses penuh ke terminal operasi"
>
    <p class="eyebrow">// VERIFIKASI PENDING</p>
    <h1 class="access-title">Verifikasi Email</h1>
    <p class="access-sub">Buka tautan aktivasi yang sudah dikirim ke email terdaftar untuk mengaktifkan akun.</p>

    <div class="info-box">
        Belum menerima email? Cek folder spam, atau minta tautan baru lewat tombol di bawah.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-ok" role="status">
            Tautan verifikasi baru sudah dikirim ke email yang Anda daftarkan.
        </div>
    @endif

    <div class="form" style="margin-top:18px">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="submit">
                <span>Kirim Ulang Tautan Verifikasi</span>
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ghost" data-test="logout-button">Keluar</button>
        </form>
    </div>

    <p class="foot-note">AKTIVITAS SESI DIREKAM · <b>IMSI CATCHER</b></p>
</x-layouts::auth.terminal>
