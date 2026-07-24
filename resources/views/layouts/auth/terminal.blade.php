@props([
    'title' => null,
    'scopeTag' => 'Sistem Monitoring Imsi Catcher',
    'scopeDesc' => 'Akuisisi · Pelacakan · Dokumentasi sinyal operasi',
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ? $title.' — '.config('app.name', 'IMSI CATCHER') : config('app.name', 'IMSI CATCHER') }}</title>
    <link rel="icon" href="{{ asset('img/logo.png') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|jetbrains-mono:400,500,600" rel="stylesheet" />
    <style>
        :root{
            --void:#060a14; --panel:#0a1120; --panel-2:#0c1526;
            --line:rgba(148,163,184,.14); --line-2:rgba(148,163,184,.22);
            --ink:#eaeefb; --muted:#8592b0; --muted-2:#5d6a88;
            --indigo:#6366f1; --indigo-2:#818cf8;
            --cyan:#22d3ee; --cyan-soft:rgba(34,211,238,.14);
            --danger:#f87171; --ok:#34d399;
            --display:'Space Grotesk',system-ui,sans-serif;
            --mono:'JetBrains Mono',ui-monospace,monospace;
        }
        *{box-sizing:border-box}
        html,body{margin:0;height:100%}
        body.term{
            font-family:var(--display); color:var(--ink); background:var(--void);
            -webkit-font-smoothing:antialiased; text-rendering:optimizeLegibility;
        }
        a{color:inherit}

        .term-stage{
            min-height:100svh; display:grid;
        }

        /* ---------- LEFT · SCOPE ---------- */
        .scope{
            position:relative; overflow:hidden;
            display:flex; flex-direction:column; justify-content:space-between;
            padding:clamp(28px,4vw,56px);
            background:
                radial-gradient(120% 90% at 15% 0%, #0e1a33 0%, var(--panel) 45%, var(--void) 100%);
            border-right:1px solid var(--line);
        }
        .scope-grid{
            position:absolute; inset:0; pointer-events:none;
            background-image:radial-gradient(rgba(129,140,248,.16) 1px, transparent 1.4px);
            background-size:26px 26px; mask-image:radial-gradient(115% 100% at 20% 10%, #000 30%, transparent 78%);
            opacity:.5;
        }
        .scope-glow{
            position:absolute; width:520px; height:520px; left:-120px; top:-140px; pointer-events:none;
            background:radial-gradient(circle, rgba(99,102,241,.28), transparent 62%); filter:blur(8px);
        }
        .scanline{
            position:absolute; left:0; right:0; height:140px; pointer-events:none; z-index:1;
            background:linear-gradient(to bottom, transparent, rgba(34,211,238,.05), transparent);
            animation:scan 7s linear infinite;
        }
        @keyframes scan{0%{transform:translateY(-160px)}100%{transform:translateY(100svh)}}

        .brand{display:flex; align-items:center; gap:12px; position:relative; z-index:2}
        .brand-glyph{width:34px; height:34px; color:var(--cyan); filter:drop-shadow(0 0 10px rgba(34,211,238,.5))}
        .brand-word{font-weight:700; letter-spacing:.22em; font-size:15px}
        .brand-word em{font-style:normal; color:var(--cyan)}
        .brand-clr{margin-left:auto; font-family:var(--mono); font-size:10px; letter-spacing:.18em;
            color:var(--indigo-2); border:1px solid var(--line-2); padding:5px 9px; border-radius:999px}

        .scope-center{position:relative; z-index:2; display:flex; flex-direction:column; align-items:center; text-align:center; gap:22px}
        .scope-tag{margin:0; font-family:var(--mono); font-size:11px; letter-spacing:.34em; color:var(--cyan); text-transform:uppercase}
        .scope-desc{margin:0; color:var(--muted); font-size:13px; letter-spacing:.02em}

        .radar{
            position:relative; width:clamp(200px,23vw,310px); aspect-ratio:1; border-radius:50%; overflow:hidden;
            border:1px solid rgba(34,211,238,.28);
            background:
                radial-gradient(circle at center, rgba(34,211,238,.06), transparent 72%),
                repeating-radial-gradient(circle at center, transparent 0 33px, rgba(34,211,238,.13) 33px 34px);
            box-shadow:inset 0 0 46px rgba(34,211,238,.08), 0 0 70px rgba(34,211,238,.06);
        }
        .radar::before{ /* crosshair */
            content:''; position:absolute; inset:0;
            background:
                linear-gradient(to right, transparent calc(50% - .5px), rgba(34,211,238,.20) calc(50% - .5px) calc(50% + .5px), transparent calc(50% + .5px)),
                linear-gradient(to bottom, transparent calc(50% - .5px), rgba(34,211,238,.20) calc(50% - .5px) calc(50% + .5px), transparent calc(50% + .5px));
        }
        .radar-sweep{
            position:absolute; inset:0; border-radius:50%;
            background:conic-gradient(from 0deg, rgba(34,211,238,.34), rgba(34,211,238,.03) 52deg, transparent 96deg, transparent 360deg);
            animation:sweep 4s linear infinite;
        }
        @keyframes sweep{to{transform:rotate(360deg)}}
        .radar-core{
            position:absolute; left:50%; top:50%; width:8px; height:8px; margin:-4px 0 0 -4px; border-radius:50%;
            background:var(--cyan); box-shadow:0 0 12px 2px rgba(34,211,238,.7);
        }
        .blip{position:absolute; width:7px; height:7px; border-radius:50%; background:var(--cyan);
            box-shadow:0 0 10px rgba(34,211,238,.9); animation:blip 3.2s ease-in-out infinite}
        .blip-1{top:29%; left:63%; animation-delay:.2s}
        .blip-2{top:65%; left:41%; animation-delay:1.1s}
        .blip-3{top:47%; left:29%; animation-delay:2.1s}
        @keyframes blip{0%,100%{opacity:.22; transform:scale(.8)}50%{opacity:1; transform:scale(1.18)}}

        .readouts{position:relative; z-index:2; margin:0; display:grid; grid-template-columns:repeat(4,auto); gap:10px 30px}
        .readouts div{display:flex; flex-direction:column; gap:3px}
        .readouts dt{margin:0; font-family:var(--mono); font-size:10px; letter-spacing:.2em; color:var(--muted-2)}
        .readouts dd{margin:0; font-family:var(--mono); font-size:13px; color:var(--ink); font-weight:500}
        .readouts dd.ok{color:var(--ok)}

        /* ---------- RIGHT · ACCESS ---------- */
        .access{
            position:relative; display:flex; align-items:center; justify-content:center;
            padding:clamp(24px,4vw,48px); background:var(--void);
        }
        .brand-mini{display:none; align-items:center; gap:10px; margin-bottom:26px}
        .brand-mini .brand-glyph{width:28px; height:28px}
        .brand-mini b{font-weight:700; letter-spacing:.2em; font-size:13px}
        .brand-mini b em{font-style:normal; color:var(--cyan)}

        .access-card{
            position:relative; width:100%; max-width:404px; padding:clamp(26px,3vw,38px);
            background:linear-gradient(180deg, var(--panel-2), var(--panel));
            border:1px solid var(--line); border-radius:16px;
            box-shadow:0 30px 80px -30px rgba(0,0,0,.8), inset 0 1px 0 rgba(255,255,255,.03);
        }
        .access-card::before{ /* top accent hairline */
            content:''; position:absolute; left:18px; right:18px; top:0; height:1px;
            background:linear-gradient(90deg, transparent, var(--indigo), var(--cyan), transparent);
            opacity:.9;
        }
        .bracket{position:absolute; width:14px; height:14px; border:1.5px solid rgba(34,211,238,.5); pointer-events:none}
        .br-tl{top:9px; left:9px; border-right:0; border-bottom:0}
        .br-tr{top:9px; right:9px; border-left:0; border-bottom:0}
        .br-bl{bottom:9px; left:9px; border-right:0; border-top:0}
        .br-br{bottom:9px; right:9px; border-left:0; border-top:0}

        .eyebrow{margin:0 0 12px; font-family:var(--mono); font-size:11px; letter-spacing:.28em; color:var(--cyan)}
        .access-title{margin:0; font-family:var(--display); font-size:clamp(24px,3vw,30px); font-weight:700; letter-spacing:-.01em}
        .access-sub{margin:8px 0 24px; color:var(--muted); font-size:13.5px; line-height:1.55}

        .form{display:flex; flex-direction:column; gap:18px}
        .field{display:flex; flex-direction:column; gap:8px; position:relative}
        .field-label{font-family:var(--mono); font-size:11px; letter-spacing:.16em; text-transform:uppercase; color:var(--muted)}
        .field-wrap{position:relative; display:flex}
        .field-input{
            width:100%; font-family:var(--display); font-size:15px; color:var(--ink);
            background:#080e1b; border:1px solid var(--line-2); border-radius:10px;
            padding:12px 14px; outline:none; transition:border-color .15s, box-shadow .15s, background .15s;
        }
        .field-wrap .field-input{padding-right:74px}
        .field-input::placeholder{color:var(--muted-2)}
        .field-input:focus{border-color:var(--cyan); background:#0a1322; box-shadow:0 0 0 3px rgba(34,211,238,.14)}
        .pw-toggle{
            position:absolute; right:6px; top:50%; transform:translateY(-50%);
            font-family:var(--mono); font-size:10px; letter-spacing:.12em; color:var(--cyan);
            background:rgba(34,211,238,.08); border:1px solid rgba(34,211,238,.25); border-radius:7px;
            padding:6px 9px; cursor:pointer; transition:background .15s}
        .pw-toggle:hover{background:rgba(34,211,238,.16)}
        .field-aux{position:absolute; right:0; top:0; font-family:var(--mono); font-size:11px; color:var(--indigo-2); text-decoration:none}
        .field-aux:hover{color:var(--cyan)}

        .remember{display:flex; align-items:center; gap:10px; font-size:13px; color:var(--muted); cursor:pointer; user-select:none}
        .remember input{appearance:none; width:17px; height:17px; border:1px solid var(--line-2); border-radius:5px; background:#080e1b; cursor:pointer; position:relative; transition:.15s}
        .remember input:checked{background:var(--indigo); border-color:var(--indigo)}
        .remember input:checked::after{content:''; position:absolute; left:5px; top:2px; width:4px; height:8px; border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg)}

        .submit{
            margin-top:4px; display:flex; align-items:center; justify-content:center; gap:9px;
            width:100%; padding:13px 16px; cursor:pointer;
            font-family:var(--display); font-size:15px; font-weight:600; letter-spacing:.02em; color:#fff;
            background:linear-gradient(180deg, var(--indigo-2), var(--indigo));
            border:1px solid rgba(129,140,248,.6); border-radius:10px;
            box-shadow:0 12px 30px -10px rgba(99,102,241,.7), inset 0 1px 0 rgba(255,255,255,.25);
            transition:transform .12s, box-shadow .2s, filter .2s;
        }
        .submit:hover{filter:brightness(1.06); box-shadow:0 16px 40px -10px rgba(34,211,238,.5), 0 0 0 1px rgba(34,211,238,.4)}
        .submit:active{transform:translateY(1px)}
        .submit svg{width:16px; height:16px}

        .alert{
            display:flex; align-items:center; gap:10px; margin-bottom:20px; padding:11px 13px;
            font-size:13px; color:#fecaca; background:rgba(248,113,113,.08);
            border:1px solid rgba(248,113,113,.3); border-radius:10px;
        }
        .alert-tag{font-family:var(--mono); font-size:10px; letter-spacing:.14em; color:var(--danger);
            border:1px solid rgba(248,113,113,.4); border-radius:5px; padding:2px 6px; flex:none}
        .alert-ok{color:#bbf7d0; background:rgba(52,211,153,.08); border-color:rgba(52,211,153,.3)}

        .info-box{
            font-size:13.5px; line-height:1.6; color:var(--muted);
            background:#080e1b; border:1px solid var(--line); border-radius:10px; padding:14px 16px;
        }

        .btn-ghost{
            display:flex; align-items:center; justify-content:center; gap:8px; width:100%;
            padding:12px 16px; cursor:pointer; font-family:var(--mono); font-size:12.5px;
            letter-spacing:.08em; color:var(--muted); background:transparent;
            border:1px solid var(--line-2); border-radius:10px; transition:border-color .15s, color .15s, background .15s;
        }
        .btn-ghost:hover{color:var(--ink); border-color:rgba(34,211,238,.4); background:rgba(34,211,238,.05)}

        .divider{display:flex; align-items:center; gap:12px; margin:2px 0; color:var(--muted-2); font-family:var(--mono); font-size:10.5px; letter-spacing:.14em}
        .divider::before,.divider::after{content:''; flex:1; height:1px; background:var(--line)}

        .back-link{margin:22px 0 0; text-align:center; font-size:13px; color:var(--muted)}
        .back-link a{color:var(--cyan); text-decoration:none; font-weight:500}
        .back-link a:hover{text-decoration:underline}

        .otp-row{display:flex; justify-content:center; gap:9px; margin:6px 0}
        .otp-box{
            width:44px; height:52px; text-align:center; font-family:var(--mono); font-size:20px; font-weight:600;
            color:var(--ink); background:#080e1b; border:1px solid var(--line-2); border-radius:10px; outline:none;
            transition:border-color .15s, box-shadow .15s;
        }
        .otp-box:focus{border-color:var(--cyan); box-shadow:0 0 0 3px rgba(34,211,238,.14)}

        .switch-mode{margin:16px 0 0; text-align:center; font-size:12.5px; color:var(--muted)}
        .switch-mode button{background:none; border:0; padding:0; cursor:pointer; color:var(--indigo-2); font-family:inherit; font-size:inherit; text-decoration:underline}
        .switch-mode button:hover{color:var(--cyan)}

        .foot-note{margin:22px 0 0; font-family:var(--mono); font-size:10.5px; letter-spacing:.1em; color:var(--muted-2); text-align:center}
        .foot-note b{color:var(--muted)}

        :focus-visible{outline:2px solid var(--cyan); outline-offset:2px}

        @media (max-width:900px){
            .term-stage{grid-template-columns:1fr}
            .scope{display:none}
            .brand-mini{display:flex}
            .access{align-items:flex-start; padding-top:52px}
        }
        @media (prefers-reduced-motion:reduce){
            .radar-sweep,.scanline,.blip{animation:none !important}
        }
    </style>
</head>
<body class="term">
    <main class="term-stage">
        <section class="access">
            <div class="access-card">
                <span class="bracket br-tl" aria-hidden="true"></span>
                <span class="bracket br-tr" aria-hidden="true"></span>
                <span class="bracket br-bl" aria-hidden="true"></span>
                <span class="bracket br-br" aria-hidden="true"></span>

                <div class="brand-mini">
                    <svg class="brand-glyph" viewBox="0 0 32 32" fill="none" aria-hidden="true" style="color:var(--cyan)">
                        <circle cx="16" cy="16" r="13" stroke="currentColor" stroke-width="1.3" opacity=".55"/>
                        <circle cx="16" cy="16" r="8" stroke="currentColor" stroke-width="1.3" opacity=".8"/>
                        <circle cx="16" cy="16" r="2.6" fill="currentColor"/>
                        <path d="M16 16 L27 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                    <b>IMSI<em> CATCHER</em></b>
                </div>

                {{ $slot }}
            </div>
        </section>
    </main>

    <script>
        (function () {
            var el = document.getElementById('term-clock');
            function pad(n){ return String(n).padStart(2,'0'); }
            function tick(){ if(!el) return; var d = new Date(); el.textContent = pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds()); }
            tick(); setInterval(tick, 1000);
        })();
        function termTogglePw(){
            var i = document.getElementById('password'), b = document.getElementById('pwBtn');
            if(!i) return;
            if(i.type === 'password'){ i.type = 'text'; b.textContent = 'SEMBUNYI'; }
            else { i.type = 'password'; b.textContent = 'LIHAT'; }
        }
    </script>
</body>
</html>
