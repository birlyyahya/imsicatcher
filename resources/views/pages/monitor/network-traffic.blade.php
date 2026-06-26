<x-layouts::app :title="__('Network Traffic')">
    <div class="space-y-6">
        <header class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h1 class="text-2xl font-semibold">Network Traffic Monitoring</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Monitor real-time throughput, latency, packet loss, dan jalankan speedtest untuk mengukur kecepatan koneksi.</p>
        </header>

        <div class="grid gap-6 md:grid-cols-1">

            <section class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold mb-4">Speedtest</h2>
                <p class="text-sm text-zinc-500 mb-4">Jalankan speedtest untuk mengukur kecepatan download dan upload koneksi Anda.</p>

                <div id="startArea" class="text-center mb-6">
                    <button id="startSpeedtestBtn" type="button" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Start Speedtest
                    </button>
                </div>

                <div id="loading" class="hidden text-center py-4">
                    <span class="loadCircle"></span>
                    Loading speedtest...
                </div>

                <div id="testWrapper" class="hidden space-y-4">
                    <div class="text-center">
                        <label for="server" class="block text-sm text-zinc-500 mb-2">Pilih server</label>
                        <select id="server" class="mx-auto rounded border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"></select>
                    </div>

                    <div class="flex gap-4 justify-between">
                        <div class="w-1/2 rounded-lg border border-zinc-300 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-xs uppercase text-zinc-500 mb-1">Download</div>
                            <div id="dlText" class="text-3xl font-bold text-blue-600">0.0 Mbps</div>
                            <div class="h-2 mt-2 overflow-hidden rounded bg-zinc-200 dark:bg-zinc-700"><div id="dlBar" class="h-2 w-0 bg-blue-500"></div></div>
                        </div>
                        <div class="w-1/2 rounded-lg border border-zinc-300 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-xs uppercase text-zinc-500 mb-1">Upload</div>
                            <div id="ulText" class="text-3xl font-bold text-green-600">0.0 Mbps</div>
                            <div class="h-2 mt-2 overflow-hidden rounded bg-zinc-200 dark:bg-zinc-700"><div id="ulBar" class="h-2 w-0 bg-green-500"></div></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg border border-zinc-300 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-xs uppercase text-zinc-500 mb-1">Ping</div>
                            <div id="pingText" class="text-2xl font-bold text-yellow-600">0 ms</div>
                        </div>
                        <div class="rounded-lg border border-zinc-300 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-xs uppercase text-zinc-500 mb-1">Jitter</div>
                            <div id="jitText" class="text-2xl font-bold text-red-600">0 ms</div>
                        </div>
                    </div>

                    <div id="ipArea" class="text-center text-sm text-zinc-500">IP Address: <span id="ip">-</span></div>

                    <div class="text-center">
                        <button id="startStopBtn" type="button" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Start</button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <style>
        .loadCircle {
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
    (function () {
        function I(id) { return document.getElementById(id); }

        // The LibreSpeed instance. Created lazily once the library is loaded.
        var s = null;

        var libUrl = "{{ asset('speedtest/speedtest.js') }}";
        var backendRoot = "{{ rtrim(url('net-speedtest'), '/') }}/";

        // Ensure the LibreSpeed library (the global `Speedtest`) is available.
        // Loaded on demand so it never depends on Livewire navigation timing
        // (the previous version failed on the first visit for this reason).
        function ensureLib(cb) {
            if (typeof window.Speedtest !== "undefined") { cb(); return; }
            var existing = document.querySelector('script[data-speedtest-lib]');
            if (existing) {
                existing.addEventListener("load", cb);
                return;
            }
            var sc = document.createElement('script');
            sc.src = libUrl;
            sc.setAttribute('data-speedtest-lib', '1');
            sc.onload = cb;
            sc.onerror = function () { console.error("Gagal memuat speedtest.js"); };
            document.head.appendChild(sc);
        }

        function buildSpeedtest() {
            var st = new Speedtest();

            // MPOT/CORS mode: required so cross-origin public servers add the
            // Access-Control-Allow-Origin header to their responses. Harmless
            // for the same-origin local server (the param is ignored there).
            st.setParameter("mpot", true);

            st.onupdate = function (data) {
                var dl = data.dlStatus || 0;
                var ul = data.ulStatus || 0;
                var ping = data.pingStatus || 0;
                var jit = data.jitterStatus || 0;
                I("dlText").textContent = parseFloat(dl).toFixed(1) + " Mbps";
                I("ulText").textContent = parseFloat(ul).toFixed(1) + " Mbps";
                I("pingText").textContent = parseInt(ping, 10) + " ms";
                I("jitText").textContent = parseInt(jit, 10) + " ms";

                var ipText = data.clientIp || "-";
                if (/^\s*<!DOCTYPE/i.test(ipText) || /^\s*<html/i.test(ipText)) {
                    ipText = "IP fetch failed";
                    console.warn("getIP returned HTML:", data.clientIp);
                }
                I("ip").textContent = ipText;

                I("dlBar").style.width = Math.min(100, (parseFloat(dl) / 200) * 100) + "%";
                I("ulBar").style.width = Math.min(100, (parseFloat(ul) / 200) * 100) + "%";
            };

            st.onend = function () {
                I("startStopBtn").innerHTML = "Start";
            };

            return st;
        }

        // Ping one server's pingURL and resolve with the latency in ms, or
        // Infinity if it's unreachable / times out. Lets us skip dead servers
        // (e.g. Singapore) and auto-pick the fastest reachable one.
        function pingServer(srv, timeoutMs) {
            return new Promise(function (resolve) {
                var base = srv.server + srv.pingURL;
                var url = base + (base.indexOf("?") >= 0 ? "&" : "?") + "cors=true&r=" + Math.random();
                var ctrl = (typeof AbortController !== "undefined") ? new AbortController() : null;
                var settled = false;
                var t0 = performance.now();
                var finish = function (val) {
                    if (settled) return;
                    settled = true;
                    clearTimeout(to);
                    resolve(val);
                };
                var to = setTimeout(function () { if (ctrl) ctrl.abort(); finish(Infinity); }, timeoutMs);
                fetch(url, { method: "GET", cache: "no-store", mode: "cors", signal: ctrl ? ctrl.signal : undefined })
                    .then(function (resp) { finish(resp.ok ? (performance.now() - t0) : Infinity); })
                    .catch(function () { finish(Infinity); });
            });
        }

        function startSpeedtest() {
            I("startArea").style.display = "none";
            I("loading").style.display = "block";
            I("loading").innerHTML = '<span class="loadCircle"></span> Memilih server tercepat...';

            ensureLib(function () {
                s = buildSpeedtest();

                // Public LibreSpeed servers (Asia first, closest to Indonesia)
                // plus the built-in local server as an offline/LAN fallback.
                var servers = [
                    { name: "Singapura (DSGroup)", server: "https://speedtest.dsgroupmedia.com/", dlURL: "backend/garbage.php", ulURL: "backend/empty.php", pingURL: "backend/empty.php", getIpURL: "backend/getIP.php" },
                    { name: "Bangalore, India (DigitalOcean)", server: "https://in1.backend.librespeed.org/", dlURL: "garbage.php", ulURL: "empty.php", pingURL: "empty.php", getIpURL: "getIP.php" },
                    { name: "Frankfurt, Jerman (Clouvider)", server: "https://fra.speedtest.clouvider.net/backend/", dlURL: "garbage.php", ulURL: "empty.php", pingURL: "empty.php", getIpURL: "getIP.php" },
                    { name: "London, Inggris (Clouvider)", server: "https://lon.speedtest.clouvider.net/backend/", dlURL: "garbage.php", ulURL: "empty.php", pingURL: "empty.php", getIpURL: "getIP.php" },
                    { name: "Los Angeles, AS (Clouvider)", server: "https://la.speedtest.clouvider.net/backend/", dlURL: "garbage.php", ulURL: "empty.php", pingURL: "empty.php", getIpURL: "getIP.php" },
                    { name: "New York, AS (Clouvider)", server: "https://nyc.speedtest.clouvider.net/backend/", dlURL: "garbage.php", ulURL: "empty.php", pingURL: "empty.php", getIpURL: "getIP.php" },
                    { name: "Server Lokal (aplikasi ini)", server: backendRoot, dlURL: "garbage", ulURL: "empty", pingURL: "empty", getIpURL: "getIP" }
                ];
                var localIdx = servers.length - 1;
                s.addTestPoints(servers);

                // Probe every server in parallel, then auto-select the fastest
                // reachable internet server. The local server is excluded from
                // auto-selection (it always wins on latency) and only used if
                // every public server is down.
                Promise.all(servers.map(function (srv) { return pingServer(srv, 4000); }))
                    .then(function (latencies) {
                        var bestIdx = -1, bestLat = Infinity;
                        latencies.forEach(function (lat, i) {
                            if (i === localIdx) return;
                            if (lat < bestLat) { bestLat = lat; bestIdx = i; }
                        });
                        if (bestIdx === -1) bestIdx = localIdx; // all public servers down

                        var sel = I("server");
                        sel.innerHTML = "";
                        servers.forEach(function (srv, i) {
                            var alive = latencies[i] !== Infinity;
                            var opt = document.createElement("option");
                            opt.value = i;
                            opt.text = srv.name + (alive ? " — " + Math.round(latencies[i]) + " ms" : " (tidak tersedia)");
                            opt.disabled = !alive && i !== bestIdx;
                            if (i === bestIdx) opt.selected = true;
                            sel.appendChild(opt);
                        });
                        sel.onchange = function () {
                            var selected = servers[this.value];
                            if (selected) s.setSelectedServer(selected);
                        };

                        s.setSelectedServer(servers[bestIdx]);

                        I("loading").style.display = "none";
                        I("testWrapper").style.display = "block";
                        I("startStopBtn").innerHTML = "Start";
                    });
            });
        }

        function startStop() {
            if (!s) return;
            var state = s.getState();
            if (state == 3) {                 // running -> abort
                s.abort();
                I("startStopBtn").innerHTML = "Start";
                return;
            }
            if (state == 0 || state == 1) {   // not ready yet
                console.warn("Speedtest server belum siap.");
                return;
            }
            if (state == 2 || state == 4) {   // ready/done -> start
                s.start();
                I("startStopBtn").innerHTML = "Abort";
            }
        }

        // Bind the controls. Uses `.onclick` so re-running on every Livewire
        // navigation is idempotent — this is what makes the button work on the
        // first visit instead of requiring a back-and-forth.
        function setup() {
            var startBtn = I("startSpeedtestBtn");
            if (!startBtn) return;            // not on this page

            startBtn.onclick = startSpeedtest;
            I("startStopBtn").onclick = startStop;

            // Reset to the initial state.
            I("startArea").style.display = "";
            I("loading").style.display = "none";
            I("testWrapper").style.display = "none";
            I("startStopBtn").innerHTML = "Start";
        }

        if (!window.__ntSpeedtestNavBound) {
            window.__ntSpeedtestNavBound = true;
            document.addEventListener("livewire:navigated", setup);
        }

        // Run immediately for the current page load / navigation.
        setup();
    })();
    </script>
</x-layouts::app>
