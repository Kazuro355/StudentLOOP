@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('partials.pagehead', ['title' => $title, 'back' => route('beranda')])

    <div class="chips" style="margin-bottom:16px">
        @foreach (['semua' => 'Semua', 'beli' => 'Beli', 'sewa' => 'Sewa'] as $k => $label)
            <a class="chip {{ $mode === $k ? 'active' : '' }}" href="{{ url()->current() }}{{ $k === 'semua' ? '' : '?mode=' . $k }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($items->isEmpty())
        <div class="card empty">
            <h3>Belum ada barang</h3>
            <p class="small">{{ $key === 'nearby' ? 'Belum ada barang di kampusmu.' : 'Coba ganti filter atau cek lagi nanti.' }}</p>
        </div>
    @else
        <div class="grid-items">
            @foreach ($items as $item) @include('partials.item-card', ['item' => $item]) @endforeach
        </div>
        {{ $items->links() }}
    @endif
@endsection
