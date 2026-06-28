@php
    // $interactive (bool), $latitude, $longitude diteruskan via @include.
    $interactive = $interactive ?? false;
    $defaultLat = \App\Models\OperasiAlat::DEFAULT_LAT;
    $defaultLng = \App\Models\OperasiAlat::DEFAULT_LNG;
    $hasCoords = filled($latitude ?? null) && filled($longitude ?? null);
@endphp

{{-- wire:ignore WAJIB: mencegah Livewire me-render ulang / merusak instance Leaflet
     setiap kali ada perubahan state lain di komponen yang sama. --}}
<div
    wire:ignore
    x-data="{
        map: null,
        marker: null,
        interactive: @js($interactive),
        searchQuery: '',
        searchResults: [],
        searching: false,
        init() {
            const hasCoords = @js($hasCoords);
            const lat = hasCoords ? {{ $latitude ?: $defaultLat }} : {{ $defaultLat }};
            const lng = hasCoords ? {{ $longitude ?: $defaultLng }} : {{ $defaultLng }};

            this.map = L.map(this.$refs.map, { scrollWheelZoom: this.interactive })
                .setView([lat, lng], hasCoords ? 15 : 8);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(this.map);

            if (hasCoords) {
                this.setMarker(lat, lng);
            }

            if (this.interactive) {
                this.map.on('click', (e) => this.pick(e.latlng.lat, e.latlng.lng));

                // Sinkronkan marker bila lat/long diubah MANUAL lewat input di form.
                this.$wire.$watch('latitude', () => this.syncFromWire());
                this.$wire.$watch('longitude', () => this.syncFromWire());
            }

            // Leaflet butuh invalidateSize saat container baru ditampilkan agar tile rapi.
            this.$nextTick(() => setTimeout(() => this.map.invalidateSize(), 150));
        },
        setMarker(lat, lng) {
            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng]).addTo(this.map);
            }
        },
        pick(lat, lng) {
            this.setMarker(lat, lng);
            this.$wire.set('latitude', lat.toFixed(7));
            this.$wire.set('longitude', lng.toFixed(7));
        },
        syncFromWire() {
            const lat = parseFloat(this.$wire.get('latitude'));
            const lng = parseFloat(this.$wire.get('longitude'));
            if (isNaN(lat) || isNaN(lng)) return;
            this.setMarker(lat, lng);
            // Recenter hanya jika titik di luar tampilan (mis. input manual jauh dari view).
            if (! this.map.getBounds().contains([lat, lng])) {
                this.map.setView([lat, lng], 13);
            }
        },
        useMyLocation() {
            if (! navigator.geolocation) {
                alert('Browser Anda tidak mendukung Geolocation.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                    this.pick(pos.coords.latitude, pos.coords.longitude);
                },
                () => alert('Gagal mengambil lokasi. Pastikan izin lokasi diaktifkan di browser.')
            );
        },
        async searchLocation() {
            if (! this.searchQuery.trim()) return;
            this.searching = true;
            this.searchResults = [];
            try {
                const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' + encodeURIComponent(this.searchQuery);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                this.searchResults = await res.json();
                if (this.searchResults.length === 0) {
                    alert('Lokasi tidak ditemukan. Coba kata kunci lain.');
                }
            } catch (e) {
                alert('Pencarian lokasi gagal. Periksa koneksi internet Anda.');
            } finally {
                this.searching = false;
            }
        },
        chooseResult(result) {
            const lat = parseFloat(result.lat);
            const lng = parseFloat(result.lon);
            this.map.setView([lat, lng], 15);
            this.pick(lat, lng);
            this.searchQuery = result.display_name;
            this.searchResults = [];
        },
    }"
>
    @if ($interactive)
        <div class="relative mb-2">
            <div class="flex gap-2">
                <input
                    type="text"
                    x-model="searchQuery"
                    @keydown.enter.prevent="searchLocation()"
                    placeholder="Cari lokasi di peta (mis. Manokwari, Papua Barat)..."
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800"
                />
                <flux:button type="button" size="sm" variant="outline" icon="magnifying-glass" @click="searchLocation()">
                    <span x-text="searching ? 'Mencari...' : 'Cari'"></span>
                </flux:button>
            </div>
            <ul
                x-show="searchResults.length"
                @click.outside="searchResults = []"
                x-cloak
                class="absolute z-[1200] mt-1 max-h-56 w-full overflow-auto rounded-lg border border-zinc-200 bg-white text-sm shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
            >
                <template x-for="result in searchResults" :key="result.place_id">
                    <li>
                        <button
                            type="button"
                            @click="chooseResult(result)"
                            class="block w-full px-3 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-700"
                            x-text="result.display_name"
                        ></button>
                    </li>
                </template>
            </ul>
        </div>
    @endif

    <div x-ref="map" class="h-[300px] w-full rounded-lg border border-zinc-200 dark:border-zinc-700"></div>

    @if ($interactive)
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <flux:button type="button" size="sm" variant="outline" icon="map-pin" @click="useMyLocation()">
                Gunakan Lokasi Saya Saat Ini
            </flux:button>
            <flux:text class="text-xs text-zinc-500">Cari lokasi, klik peta, atau isi Latitude/Longitude manual di bawah.</flux:text>
        </div>
    @endif
</div>
