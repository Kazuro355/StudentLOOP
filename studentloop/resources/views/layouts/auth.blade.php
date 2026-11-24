<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#5b3de0">
    <title>@yield('title', 'Masuk') · StudentLOOP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="is-auth">
<div class="auth">
    <aside class="auth-aside">
        <a class="logo" href="{{ url('/') }}">StudentLOOP</a>
        <h1>Beli, sewa, dan jual barang sesama Binusian.</h1>
        <ul>
            <li>Khusus mahasiswa Binusian</li>
            <li>Cari barang di sekitar kampus</li>
            <li>Chat langsung dengan pemilik</li>
        </ul>
    </aside>

    <section class="auth-main">
        <div class="auth-card">
            @include('partials.flash')
            @yield('content')
        </div>
    </section>
</div>
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
