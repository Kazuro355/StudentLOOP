@extends('layouts.app')
@section('title', 'Favorit')

@section('content')
    @include('partials.pagehead', ['title' => 'Favorit', 'subtitle' => 'Barang yang kamu simpan', 'back' => route('beranda')])

    @if ($items->isEmpty())
        <div class="card empty">
            <h3>Belum ada favorit</h3>
            <p class="small">Tekan ikon hati di kartu barang untuk menyimpannya di sini.</p>
            <a class="btn" href="{{ route('search') }}" style="margin-top:12px">Jelajahi barang</a>
        </div>
    @else
        <div class="grid-items">
            @foreach ($items as $item) @include('partials.item-card', ['item' => $item]) @endforeach
        </div>
        {{ $items->links() }}
    @endif
@endsection
