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
                    <button id="startSpeedtestBtn" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
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
                        <button id="startStopBtn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Start</button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <style>
        .meter {
            width: 100px;
            height: 100px;
            display: block;
            margin: 0 auto;
        }
        .meterText {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }
        .unit {
            font-size: 12px;
            text-align: center;
            margin-top: 5px;
        }
        .testArea {
            display: inline-block;
            width: 50%;
            vertical-align: top;
        }
        .testArea2 {
            display: inline-block;
            width: 50%;
            vertical-align: top;
        }
        .testName {
            text-align: center;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .testGroup {
            margin-bottom: 20px;
        }
        #startStopBtn {
            display: block;
            margin: 0 auto 20px auto;
            width: 120px;
            height: 40px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        #startStopBtn:hover {
            background-color: #0056b3;
        }
        #serverArea {
            text-align: center;
            margin-bottom: 20px;
        }
        #ipArea {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .loadCircle {
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script src="{{ asset('speedtest/speedtest.js') }}"></script>
    <script src="{{ asset('speedtest/speedtest_worker.js') }}"></script>
    <script>

        var s = new Speedtest();
        var meterBk = '#d4edda';
        var dlColor = '#007bff';
        var ulColor = '#28a745';
        var pingColor = '#ffc107';
        var jitColor = '#dc3545';

        function I(i) { return document.getElementById(i); }

        function startSpeedtest() {
            I("startArea").style.display = "none";
            I("loading").style.display = "block";

            // Initialize the speedtest after a short delay
            setTimeout(function() {
                var backendRoot = "{{ rtrim(asset('speedtest/backend'), '/') }}" + "/";
                var servers = [
                    { name: "Local Server", server: backendRoot, dlURL: "garbage.php", ulURL: "empty.php", pingURL: "empty.php", getIpURL: "getIP.php" }
                ];
                s.addTestPoints(servers);
                s.setSelectedServer(servers[0]);
                // Update the server dropdown
                var sel = I("server");
                sel.innerHTML = "";
                for (var i = 0; i < servers.length; i++) {
                    var opt = document.createElement("option");
                    opt.value = i;
                    opt.text = servers[i].name;
                    if (i === 0) opt.selected = true;
                    sel.appendChild(opt);
                }
                sel.onchange = function () {
                    var selected = servers[this.value];
                    if (selected) {
                        s.setSelectedServer(selected);
                    }
                };
                I("loading").style.display = "none";
                I("testWrapper").style.display = "block";
                I("startStopBtn").innerHTML = "Start";
            }, 1000);
        }

        function startStop() {
            var state = s.getState();
            if (state == 3) {
                s.abort();
                I("startStopBtn").innerHTML = "Start";
                I("shareArea").classList.remove("hidden");
                return;
            }
            if (state == 0 || state == 1) {
                console.warn("Speedtest server belum siap.");
                return;
            }
            if (state == 2 || state == 4) {
                s.start();
                I("startStopBtn").innerHTML = "Abort";
                return;
            }
        }

        function initUI() {
            I("dlText").textContent = "";
            I("ulText").textContent = "";
            I("pingText").textContent = "";
            I("jitText").textContent = "";
            I("ip").textContent = "";
            I("startStopBtn").innerHTML = "Start";
        }

        s.onupdate = function (data) {
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
                ipText = "IP fetch failed (404)";
                console.warn("getIP returned HTML:", data.clientIp);
            }
            I("ip").textContent = ipText;
            I("dlBar").style.width = Math.min(100, (parseFloat(dl) / 200) * 100) + "%";
            I("ulBar").style.width = Math.min(100, (parseFloat(ul) / 200) * 100) + "%";
        };
        s.onend = function (aborted) {
            I("startStopBtn").innerHTML = "Start";
            if (!aborted) {
                // Test completed
                // I("shareArea").classList.remove("hidden");
            }
            I("loading").classList.add("hidden");
            I("testWrapper").classList.add("hidden");
            I("startArea").classList.remove("hidden");
            updateMeter({dlStatus: s.getDlStatus(), ulStatus: s.getUlStatus(), pingStatus: s.getPingStatus(), jitterStatus: s.getJitterStatus()});
        };
        function updateMeter(data) {
            // Update canvas meters here if needed
        }

        document.addEventListener("livewire:navigated", function () {
            I("startSpeedtestBtn").addEventListener("click", startSpeedtest);
            I("startStopBtn").addEventListener("click", startStop);
        });
    </script>
</x-layouts::app>
