@extends('layouts.app')
@section('title', 'Panduan pencarian')

@section('content')
    @include('partials.pagehead', ['title' => 'Panduan pencarian', 'back' => route('beranda')])

    <div class="cols c-1-1">
        <div class="card stack">
            <h3>Cara menemukan barang yang pas</h3>
            <ol class="steps-ol">
                <li>Ketik nama barang, kategori, atau kampus di kolom cari.</li>
                <li>Pilih <b>Beli</b> atau <b>Sewa</b> sesuai kebutuhanmu.</li>
                <li>Atur rentang harga dan kondisi barang.</li>
                <li>Pilih kampus terdekat supaya serah terima mudah.</li>
                <li>Urutkan berdasarkan terbaru, termurah, atau terpopuler.</li>
            </ol>
            <a class="btn" href="{{ route('search') }}" style="justify-self:start">Buka Cari &amp; Filter</a>
        </div>

        <div class="card tint stack">
            <h3>Tidak ketemu?</h3>
            <p class="muted small">Buat permintaan di <b>Wanted</b>. Pemilik barang bisa menawarkan barangnya langsung ke kamu.</p>
            <a class="btn btn-outline" href="{{ route('wanted.create') }}" style="justify-self:start">Buat permintaan Wanted</a>
            <hr class="divider">
            <h3>Tips transaksi aman</h3>
            <ul class="muted small" style="margin:0;padding-left:18px;display:grid;gap:6px">
                <li>Bertemu di area kampus yang ramai.</li>
                <li>Cek kondisi dan kelengkapan barang saat serah terima.</li>
                <li>Bayar lewat StudentLOOP supaya tercatat di transaksi.</li>
                <li>Laporkan pengguna atau barang yang mencurigakan.</li>
            </ul>
        </div>
    </div>
@endsection
