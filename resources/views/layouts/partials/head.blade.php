<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#263C92">

<!-- Ikon tab: logo IPB -->
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

<title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name', 'Peminjaman Alat') }}</title>

<!-- Fonts (bunny.net: alternatif Google Fonts yang ramah privasi) -->
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|figtree:400,500,600&display=swap" rel="stylesheet" />

<!-- Scripts -->
@vite(['resources/css/app.css', 'resources/js/app.js'])
