<x-layouts::auth.terminal
    :title="__('Verifikasi dua faktor')"
    scope-tag="Two-Factor Checkpoint"
    scope-desc="Lapisan verifikasi kedua sebelum masuk terminal"
>
    <p class="eyebrow">// LAPISAN KEDUA</p>

    <div id="mode-otp">
        <h1 class="access-title">Kode Otentikasi</h1>
        <p class="access-sub">Masukkan kode 6 digit dari aplikasi authenticator Anda.</p>
    </div>

    <div id="mode-recovery" style="display:none">
        <h1 class="access-title">Kode Pemulihan</h1>
        <p class="access-sub">Masukkan salah satu kode pemulihan darurat akun Anda.</p>
    </div>

    @if ($errors->any())
        <div class="alert" role="alert">
            <span class="alert-tag">DITOLAK</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="form">
        @csrf

        <div id="otp-fields">
            <div class="otp-row">
                @for ($i = 0; $i < 6; $i++)
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        class="otp-box"
                        aria-label="Digit {{ $i + 1 }}"
                        oninput="termOtpInput(this, {{ $i }})"
                        onkeydown="termOtpBackspace(event, {{ $i }})"
                        @if ($i === 0) autofocus @endif
                    >
                @endfor
            </div>
            <input type="hidden" name="code" id="code-hidden">
        </div>

        <div id="recovery-field" style="display:none">
            <label class="field" for="recovery_code">
                <span class="field-label">Kode Pemulihan</span>
                <input
                    id="recovery_code"
                    name="recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                    class="field-input"
                >
            </label>
            @error('recovery_code')
                <p style="color:var(--danger); font-size:12.5px; margin:6px 0 0">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="submit">
            <span>Lanjutkan</span>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M4 10h11m0 0-4-4m4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>

    <p class="switch-mode">
        <span id="switch-to-recovery">Atau <button type="button" onclick="termToggle2fa()">gunakan kode pemulihan</button></span>
        <span id="switch-to-otp" style="display:none">Atau <button type="button" onclick="termToggle2fa()">gunakan kode authenticator</button></span>
    </p>

    <script>
        function termOtpInput(el, idx) {
            el.value = el.value.replace(/[^0-9]/g, '');
            var boxes = document.querySelectorAll('#otp-fields .otp-box');
            if (el.value && idx < boxes.length - 1) {
                boxes[idx + 1].focus();
            }
            var code = Array.from(boxes).map(function (b) { return b.value; }).join('');
            document.getElementById('code-hidden').value = code;
        }
        function termOtpBackspace(evt, idx) {
            var boxes = document.querySelectorAll('#otp-fields .otp-box');
            if (evt.key === 'Backspace' && !boxes[idx].value && idx > 0) {
                boxes[idx - 1].focus();
            }
        }
        function termToggle2fa() {
            var otpMode = document.getElementById('mode-otp');
            var recMode = document.getElementById('mode-recovery');
            var otpFields = document.getElementById('otp-fields');
            var recField = document.getElementById('recovery-field');
            var toRecovery = document.getElementById('switch-to-recovery');
            var toOtp = document.getElementById('switch-to-otp');
            var showingOtp = otpFields.style.display !== 'none';

            otpMode.style.display = showingOtp ? 'none' : '';
            recMode.style.display = showingOtp ? '' : 'none';
            otpFields.style.display = showingOtp ? 'none' : '';
            recField.style.display = showingOtp ? '' : 'none';
            toRecovery.style.display = showingOtp ? 'none' : '';
            toOtp.style.display = showingOtp ? '' : 'none';

            document.querySelectorAll('#otp-fields .otp-box').forEach(function (b) { b.value = ''; });
            document.getElementById('code-hidden').value = '';
            document.getElementById('recovery_code').value = '';

            if (showingOtp) {
                document.getElementById('recovery_code').focus();
            } else {
                document.querySelector('#otp-fields .otp-box').focus();
            }
        }
    </script>
</x-layouts::auth.terminal>
