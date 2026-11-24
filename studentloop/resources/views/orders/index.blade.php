@extends('layouts.app')
@section('title', $loansOnly ? 'Pinjaman saya' : 'Transaksi & pengembalian')

@section('content')
    @include('partials.pagehead', [
        'title' => $loansOnly ? 'Pinjaman saya' : 'Transaksi & pengembalian',
        'subtitle' => 'Pantau status pembayaran, serah terima, dan pengembalian.',
    ])

    <div class="row-between wrap" style="margin-bottom:16px">
        <div class="tabs">
            <a class="tab {{ $role === 'buyer' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['peran' => 'buyer', 'page' => null]) }}">{{ $loansOnly ? 'Saya meminjam' : 'Saya membeli / menyewa' }}</a>
            @unless ($loansOnly)
                <a class="tab {{ $role === 'seller' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['peran' => 'seller', 'page' => null]) }}">Saya memiliki</a>
            @endunless
        </div>
        <div class="chips">
            <a class="chip {{ $status === 'aktif' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => 'aktif', 'page' => null]) }}">Aktif</a>
            <a class="chip {{ $status === 'selesai' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => 'selesai', 'page' => null]) }}">Riwayat</a>
            @unless ($loansOnly)
                <a class="chip soft" href="{{ route('orders.loans') }}">Hanya sewa</a>
            @endunless
        </div>
    </div>

    @forelse ($orders as $order)
        @php($other = $role === 'buyer' ? $order->seller : $order->buyer)
        <a class="card tight order-row" style="margin-bottom:12px" href="{{ route('orders.show', $order) }}">
            <div class="order-thumb">@if ($order->item->cover_url)<img src="{{ $order->item->cover_url }}" alt="">@else {{ $order->item->title }} @endif</div>
            <div class="grow stack" style="gap:4px">
                <div class="row-between wrap">
                    <b>{{ $order->item->title }}</b>
                    <span class="badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
                </div>
                <p class="muted small">{{ $order->code }} · {{ $order->isRent() ? 'Sewa' : 'Beli' }} · {{ $role === 'buyer' ? 'Pemilik' : 'Peminjam/pembeli' }}: {{ $other->name }}</p>
                @if ($order->isRent())
                    <p class="muted small">{{ $order->start_date->translatedFormat('j M') }} – {{ $order->end_date->translatedFormat('j M Y') }} ({{ $order->days }} hari)</p>
                @endif
                <p class="primary bold">@rp($order->total)</p>
            </div>
        </a>
    @empty
        <div class="card empty">
            <h3>{{ $status === 'aktif' ? 'Belum ada transaksi aktif' : 'Belum ada riwayat' }}</h3>
            <p class="small">Mulai dengan menjelajahi barang dari sesama Binusian.</p>
            <a class="btn" href="{{ route('search') }}" style="margin-top:12px">Jelajahi barang</a>
        </div>
    @endforelse

    {{ $orders->links() }}
@endsection
