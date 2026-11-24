@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
    <div class="home-top">
        <section class="hero stack">
            <div class="eyebrow">Khusus mahasiswa BINUS</div>
            <h1>Beli, jual, sewa. Sesama Binusian.</h1>
            <p>Kebutuhan kuliah lebih mudah, barangmu lebih berguna.</p>
            <a class="btn btn-block" href="{{ route('search') }}">Jelajahi barang</a>
        </section>

        <section class="promo stack">
            <h2>Punya barang nganggur?</h2>
            <p class="small">Jual atau sewakan barangmu untuk bantu teman kampus.</p>
            <div class="btn-row">
                <a class="btn btn-outline" href="{{ route('items.create.sell') }}">Jual barang</a>
                <a class="btn" href="{{ route('items.create.rent') }}">Sewakan barang</a>
            </div>
        </section>
    </div>

    <div class="chips section" style="margin-top:20px">
        @foreach (['semua' => 'Semua', 'beli' => 'Beli', 'sewa' => 'Sewa'] as $key => $label)
            <a class="chip {{ $mode === $key ? 'active' : '' }}" href="{{ route('beranda', $key === 'semua' ? [] : ['mode' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <section class="section" style="margin-top:20px">
        <h2 style="margin-bottom:12px">Jelajahi kategori</h2>
        <div class="chips">
            @foreach ($categories as $category)
                <a class="chip" href="{{ route('search', ['kategori' => [$category->id]]) }}">{{ $category->name }}</a>
            @endforeach
        </div>
        <div class="chips" style="margin-top:10px">
            <a class="chip soft" href="{{ route('wanted.index') }}">Wanted · Cari kebutuhan</a>
            <a class="chip soft" href="{{ route('notifications.index') }}">Notifikasi</a>
            <a class="chip soft" href="{{ route('favorites.index') }}">Favorit</a>
            <a class="chip soft" href="{{ route('items.mine') }}">Barang saya</a>
            <a class="chip soft" href="{{ route('orders.index') }}">Transaksi</a>
        </div>
    </section>

    @foreach ($sections as $key => $section)
        <section class="section">
            <div class="section-head">
                <h2>{{ $section['title'] }}</h2>
                <a class="link small" href="{{ route($section['route'], $mode === 'semua' ? [] : ['mode' => $mode]) }}">Lihat semua</a>
            </div>
            @if ($section['items']->isEmpty())
                <div class="card empty">
                    <h3>Belum ada barang</h3>
                    <p class="small">{{ $key === 'nearby' ? 'Belum ada barang di kampusmu. Jadi yang pertama menawarkan!' : 'Belum ada barang untuk bagian ini.' }}</p>
                </div>
            @else
                <div class="grid-items {{ $key === 'popular' ? 'five' : '' }}">
                    @foreach ($section['items'] as $item) @include('partials.item-card', ['item' => $item]) @endforeach
                </div>
            @endif
        </section>
    @endforeach

    <div class="notice section">
        <b>Temukan yang paling pas</b><br>
        Gunakan <a class="link" href="{{ route('search') }}">Cari &amp; Filter</a> untuk nama/kategori, Beli/Sewa, harga, kondisi, kampus, dan urutan terbaru. Simpan Favorit untuk kabar harga dan ketersediaan.
        <a class="link" href="{{ route('search.guide') }}">Panduan pencarian</a>
    </div>
@endsection
