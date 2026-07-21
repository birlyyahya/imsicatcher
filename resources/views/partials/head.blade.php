<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="{{ asset('img/logo.png') }}" sizes="any">
<link rel="icon" href="{{ asset('img/logo.png') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|space-grotesk:500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
<script>
    // Default tema gelap "terminal intel": hanya berlaku bila user belum
    // memilih appearance sendiri lewat halaman Settings > Appearance.
    if (! localStorage.getItem('flux.appearance')) {
        localStorage.setItem('flux.appearance', 'dark');
    }
</script>
@fluxAppearance
