<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#5b3de0">
    <title>@yield('title', 'Beranda') · StudentLOOP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $authUser = auth()->user();
    $navHome = request()->routeIs('beranda', 'items.popular', 'items.newest', 'items.nearby', 'items.recommended', 'search', 'search.guide', 'items.show');
    $navFav = request()->routeIs('favorites.*');
    $navOrders = request()->routeIs('orders.*', 'payments.*', 'reviews.*');
    $navChat = request()->routeIs('conversations.*');
    $navWanted = request()->routeIs('wanted.*');
    $navNotif = request()->routeIs('notifications.*');
    $navProfile = request()->routeIs('profile.*', 'users.*', 'reports.*', 'items.mine', 'items.create.*', 'items.edit');
@endphp

<header class="topbar">
    <div class="topbar-inner">
        <a class="logo" href="{{ route('beranda') }}">StudentLOOP</a>

        @unless(request()->routeIs('admin.*'))
            <form class="search" action="{{ route('search') }}" method="GET" role="search">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari barang, kategori, atau lokasi..." aria-label="Cari barang">
            </form>
        @endunless

        <nav class="nav-links" aria-label="Navigasi utama">
            <a href="{{ route('beranda') }}" class="{{ $navHome ? 'active' : '' }}">Beranda</a>
            <a href="{{ route('favorites.index') }}" class="{{ $navFav ? 'active' : '' }}">Favorit</a>
            <a href="{{ route('orders.index') }}" class="{{ $navOrders ? 'active' : '' }}">Transaksi</a>
            <a href="{{ route('conversations.index') }}" class="{{ $navChat ? 'active' : '' }}">Pesan @if($navUnreadMessages)<span class="dot">{{ $navUnreadMessages }}</span>@endif</a>
            <a href="{{ route('wanted.index') }}" class="{{ $navWanted ? 'active' : '' }}">Wanted</a>
            <a href="{{ route('notifications.index') }}" class="{{ $navNotif ? 'active' : '' }}">Notifikasi @if($navUnreadNotifs)<span class="dot">{{ $navUnreadNotifs }}</span>@endif</a>
            @if($authUser->isAdmin())<a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Admin</a>@endif
            @if($authUser->isAdmin())<a href="{{ route('admin.payouts') }}" class="{{ request()->routeIs('admin.payouts*') ? 'active' : '' }}">Dana sewa</a>@endif
        </nav>

        <div class="top-actions">
            <a class="icon-btn bell" href="{{ route('notifications.index') }}" aria-label="Notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                @if($navUnreadNotifs)<span class="dot">{{ $navUnreadNotifs }}</span>@endif
            </a>
            <a class="avatar hide-mobile" href="{{ route('profile.show') }}" title="{{ $authUser->name }}" aria-label="Profil">{{ $authUser->initials() }}</a>
        </div>
    </div>
</header>

<main class="container">
    @include('partials.flash')
    @yield('content')
</main>

<nav class="bottom-nav" aria-label="Navigasi bawah">
    @if($authUser->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Ringkasan</a>
        <a href="{{ route('admin.reports') }}" class="{{ request()->routeIs('admin.reports*') ? 'active' : '' }}">Laporan</a>
        <a href="{{ route('admin.payouts') }}" class="{{ request()->routeIs('admin.payouts*') ? 'active' : '' }}">Dana</a>
        <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">Pengguna</a>
        <a href="{{ route('beranda') }}">Marketplace</a>
    @else
        <a href="{{ route('beranda') }}" class="{{ $navHome ? 'active' : '' }}">Beranda</a>
        <a href="{{ route('conversations.index') }}" class="{{ $navChat ? 'active' : '' }}">Pesan @if($navUnreadMessages)<span class="dot">{{ $navUnreadMessages }}</span>@endif</a>
        <a href="{{ route('orders.index') }}" class="{{ $navOrders ? 'active' : '' }}">Transaksi</a>
        <a href="{{ route('profile.show') }}" class="{{ $navProfile ? 'active' : '' }}">Profil</a>
    @endif
</nav>

<script src="{{ asset('js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>
